<?php

/**
 * This class is responsible to process refund-related requests on the Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRefundRepository;
use StellarPay\PaymentGateways\Stripe\Services\RefundService as BaseRefundService;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\RefundDataStrategy;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\RefundDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\RefundDTO as StripeResponseRefundDTO;
use WC_Order;

/**
 * @since 1.0.0
 */
class RefundService
{
    /**
     * @since 1.0.0
     */
    protected BaseRefundService $refundService;

    /**
     * @since 1.0.0
     */
    protected PaymentIntentService $paymentIntentService;

    /**
     * @since 1.0.0
     */
    protected OrderRefundRepository $orderRefundRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        BaseRefundService $refundService,
        PaymentIntentService $paymentIntentService,
        OrderRefundRepository $orderRefundRepository
    ) {
        $this->refundService = $refundService;
        $this->paymentIntentService = $paymentIntentService;
        $this->orderRefundRepository = $orderRefundRepository;
    }

    /**
     * This method creates a refund.
     *
     * @since 1.0.0
     * @throws StripeAPIException|Exception
     */
    public function create(WC_Order $order, float $amount, string $reason): bool
    {
        // If the order has no transaction ID, then we can't refund it on the Stripe.
        if (! $order->get_transaction_id()) {
            throw new Exception(esc_html__('Refund request is not acceptable for order without the Stripe payment.', 'stellarpay')); // phpcs:ignore
        }

        $paymentIntent = $this->paymentIntentService->getPaymentIntent($order->get_transaction_id());

        // If the payment intent is not completed, then we can't refund it on the Stripe.
        if (! $paymentIntent->isSucceeded()) {
            throw new Exception(esc_html__('Refund request is not acceptable for the incomplete Stripe payment.', 'stellarpay')); // phpcs:ignore
        }

        $refundDataStrategy = new RefundDataStrategy($amount, $order, $reason);
        $refundDTO = RefundDTO::fromCustomerDataStrategy($refundDataStrategy);

        $refund = $this->refundService->createRefund($refundDTO);

        $this->addOrderNotes($refund, $order, $amount);
        $this->attachStripeRefundIdToRecentOrderRefund($order, $refund->getId());

        return true;
    }

    /**
     * Attach Stripe refund ID to the recent order refund.
     *
     * @since 1.0.0
     */
    protected function attachStripeRefundIdToRecentOrderRefund(WC_Order $order, $stripeRefundId): void
    {
        $refunds = $order->get_refunds();
        $recentRefund = current($refunds);

        $this->orderRefundRepository->setRefundId($recentRefund, $stripeRefundId);
        $recentRefund->save();
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function addOrderNotes(StripeResponseRefundDTO $refund, WC_Order $order, $refundedAmount): void
    {
        $formattedAmount = wc_price($refundedAmount, ['currency' => $order->get_currency()]);

        // If the refund is failed or canceled, then we can't refund it on the Stripe.
        // This will help to delete refund from the WooCommerce order.
        if ($refund->isFailed() || $refund->isCanceled()) {
            throw new Exception(
                sprintf(
                /* translators: 1. Refund status */
                    esc_html__('Refund failed for the Stripe payment with status %1$s', 'stellarpay'),
                    esc_html($refund->getRefundStatus())
                )
            );
        }

        // Add refund note to the order based on refund status on the Stripe.
        if ($refund->isSuccessful()) {
            $refundMessage = sprintf(
            /* translators: 1. amount (formatted) 2, the Stripe refund id */
                esc_html__('Refunded %1$s on the Stripe. Refund ID is %2$s.', 'stellarpay'),
                $formattedAmount,
                $refund->getId(),
            );

            $order->add_order_note($refundMessage);
        } elseif ($refund->isPending() || $refund->isRequiresAction()) {
            $refundMessage = sprintf(
            /* translators: 1. amount (formatted) 2, the Stripe refund id */
                esc_html__('Refund created on the Stripe for amount %1$s with id %2$s with status %1$s', 'stellarpay'),
                $formattedAmount,
                $refund->getId(),
                $refund->getRefundStatus()
            );

            $order->add_order_note($refundMessage);
        }
    }
}
