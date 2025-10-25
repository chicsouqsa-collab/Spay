<?php

/**
 * ChargeUpdated event processor for Stripe.
 *
 * This class is responsible for processing the charge.updated event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderEventProcessor;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService;
use WC_Order;

/**
 * @since 1.0.0
 */
class ChargeUpdated extends OrderEventProcessor
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
        OrderRepository $orderRepository,
        PaymentIntentService $paymentIntentService
    ) {
        parent::__construct($eventResponse, $orderRepository);

        $this->paymentIntentService = $paymentIntentService;
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    protected function processOrder(WC_Order $order, EventDTO $eventDTO): void
    {
        // We should save the Stripe fee at once.
        if ($this->orderRepository->getPaymentIntentFee($order)) {
            return;
        }

        $paymentIntentDTO = $this->paymentIntentService->getPaymentIntent(
            $eventDTO->getPaymentIntentId(),
            ['expand' => ['latest_charge.balance_transaction']]
        );

        $fee = $paymentIntentDTO->getFee();
        $this->orderRepository->setPaymentIntentFee($order, $fee);

        $order->add_order_note(
            sprintf(
                /* translators: 1: Fee 2: Net Amount */
                esc_html__('Stripe charged %1$s fee and net amount is %2$s.', 'stellarpay'),
                wc_price($fee->getAmount()),
                wc_price($paymentIntentDTO->getNetAmount()->getAmount())
            )
        );
    }

    /**
     * This function returns whether the order has acceptable status.
     *
     * @since 1.6.0 Use OrderStatus.
     * @since 1.0.0
     */
    protected function isOrderInAcceptableStatus(WC_Order $order): bool
    {
        return in_array($order->get_status(), [OrderStatus::PENDING, OrderStatus::ON_HOLD, OrderStatus::COMPLETED, OrderStatus::PROCESSING], true);
    }
}
