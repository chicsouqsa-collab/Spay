<?php

/**
 * This class is responsible for processing the invoice.paid event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Hooks;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\SubscriptionInvoiceEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\InvoiceEventDTO;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Data_Exception;

/**
 * @since 1.0.0
 */
class InvoicePaid extends SubscriptionInvoiceEventProcessor
{
    /**
     * @since 1.0.0
     */
    protected PaymentIntentService $paymentIntentService;

    /**
     * @since 1.0.0
     */
    public function __construct(
        EventResponse $eventResponse,
        RenewalOrderRepository $renewalOrderRepository,
        PaymentIntentService $paymentIntentService
    ) {
        parent::__construct($eventResponse, $renewalOrderRepository);

        $this->paymentIntentService = $paymentIntentService;
    }

    /**
     * @since 1.3.0 Set subscription status to active after invoice paid.
     * @since 1.0.0
     *
     * @throws WC_Data_Exception
     * @throws BindingResolutionException|Exception
     */
    protected function processSubscriptionInvoice(Subscription $subscription, InvoiceEventDTO $invoiceEventDTO): void
    {
        $this->createOrder($subscription, $invoiceEventDTO);
        $subscription->deleteNewPaymentMethodForRenewal();

        $subscription->billedCount = ++$subscription->billedCount;
        $subscription->nextBillingAt = $subscription->calculateNextBillingDate();
        $subscription->nextBillingAtGmt = Temporal::getGMTDateTime($subscription->nextBillingAt);

        $subscription->updateStatus(SubscriptionStatus::ACTIVE());

        $subscription->save();
    }

    /**
     * @since 1.8.0 Add total and subtotal to order for line-item.
     * @since 1.0.0
     * @throws WC_Data_Exception
     * @throws StripeAPIException
     */
    protected function createOrder(Subscription $subscription, InvoiceEventDTO $invoiceEventDTO): int
    {
        $parentOrder = wc_get_order($subscription->firstOrderId);

        $renewalOrder = wc_create_order();

        $renewalOrder->set_parent_id($parentOrder->get_id());

        $renewalOrder->set_customer_id($parentOrder->get_customer_id());
        $renewalOrder->set_address($parentOrder->get_address());
        $renewalOrder->set_address($parentOrder->get_address('shipping'), 'shipping');

        /* @var \WC_Order_Item_Product $orderItem Order item. */
        $orderItem = $parentOrder->get_item($subscription->firstOrderItemId);
        $product = $orderItem->get_product(); // @phpstan-ignore-line
        $renewalOrder->add_product(
            $product,
            $orderItem->get_quantity(),
            [
                'subtotal' => (string)$invoiceEventDTO->getAmountPaid()->getAmount(),
                'total' => (string)wc_get_price_including_tax(
                    $product,
                    ['price' => $invoiceEventDTO->getAmountPaid()->getAmount()]
                )
            ]
        );

        $renewalOrder->set_payment_method($parentOrder->get_payment_method());
        $renewalOrder->set_payment_method_title($parentOrder->get_payment_method_title());

        // @todo: decode which meta should be store ot renewal order.
        $renewalOrder->calculate_totals();

        $renewalOrder->save();

        $paymentGatewayMode = $this->renewalOrderRepository->getPaymentGatewayMode($parentOrder);
        $paymentIntentDTO = $this->paymentIntentService->getPaymentIntent(
            $invoiceEventDTO->getPaymentIntentId(),
            ['expand' => ['latest_charge.balance_transaction']]
        );

        $this->renewalOrderRepository->setCustomerId($renewalOrder, $paymentIntentDTO->getCustomer());
        $this->renewalOrderRepository->setPaymentGatewayMode($renewalOrder, $paymentGatewayMode);
        $this->renewalOrderRepository->setPaymentMethodId($renewalOrder, $paymentIntentDTO->getPaymentMethod());
        $this->renewalOrderRepository->setPaymentIntentFee($renewalOrder, $paymentIntentDTO->getFee());
        $this->renewalOrderRepository->setRenewalSubscriptionId($renewalOrder, $subscription->id);

        $renewalOrder->payment_complete($invoiceEventDTO->getPaymentIntentId());

        $renewalOrder->add_order_note(
            sprintf(
                /* translators: 1: Fee 2: Net Amount */
                esc_html__('Stripe charged %1$s fee and net amount is %2$s.', 'stellarpay'),
                wc_price($paymentIntentDTO->getFee()->getAmount()),
                wc_price($paymentIntentDTO->getNetAmount()->getAmount())
            )
        );

        $renewalOrder->add_order_note(
            sprintf(
                /* translators: 1: Subscription id */
                esc_html__('Renewal payment paid for subscription #%1$s.', 'stellarpay'),
                $subscription->id,
                $orderItem->get_name()
            )
        );

        /**
         * Fires after a subscription renewal order is created.
         *
         * @since 1.9.1
         *
         * @param \WC_Order $renewalOrder The renewal order
         * @param \StellarPay\Subscriptions\Models\Subscription $subscription The subscription
         */
        Hooks::doAction('stellarpay_wc_stripe_renewal_order_created', $renewalOrder, $subscription);


        return $renewalOrder->get_id();
    }
}
