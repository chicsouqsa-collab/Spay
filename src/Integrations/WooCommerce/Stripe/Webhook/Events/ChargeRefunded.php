<?php

/**
 * ChargeRefunded event processor for Stripe.
 *
 * This class is responsible for processing the charge.refunded event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use Exception;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderRefundEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\RefundEventDTO;
use WC_Order;
use WC_Order_Refund;

/**
 * Class ChargeRefunded
 *
 * @since 1.0.0
 */
class ChargeRefunded extends OrderRefundEventProcessor
{
    /**
     * @since 1.0.0
     *
     * @throws Exception|\StellarPay\Core\Exceptions\Primitives\Exception
     */
    protected function processOrderRefund(
        WC_Order $order,
        RefundEventDTO $event,
        WC_Order_Refund $refund = null
    ): ?WC_Order_Refund {
        // If a refund is found, then we do not need to process it to prevent double refunding.
        $refund = $this->getOrderRefund($order, $event->getRefundId());
        if ($refund) {
            return null;
        }

        $stripeRefundId = $event->getRefundId();
        $refundedAmount = Money::fromMinorAmount($event->getRefundAmount(), $order->get_currency('edit'));
        $refundedBy = $event->isRefundedFromWebsite() ? ModifierContextType::ADMIN : ModifierContextType::WEBHOOK;
        $isPartialRefund = $event->isPartialRefund();

        // Create the refund.
        $newRefund = wc_create_refund(
            [
                'order_id' => $order->get_id(),
                'amount' => $refundedAmount->getAmount(),
                // translators: refunded via `StellarPay Dashboard` or `Stripe Dashboard`.
                'reason' => sprintf(esc_html__('Refunded via %s', 'stellarpay'), $refundedBy),
            ]
        );

        // Update the refund ID.
        if (is_wp_error($newRefund)) {
            throw new \StellarPay\Core\Exceptions\Primitives\Exception(esc_attr($newRefund->get_error_message()));
        }

        $this->orderRefundRepository->setRefundId($newRefund, $stripeRefundId);
        $newRefund->save();

        $reason = $event->getRefundReason();

        $subscriptionIdMessage = '';

        if ($subscriptionId = $event->getRefundedSubscriptionId()) {
            $subscriptionIdMessage = sprintf(
                /* translators: 1: Subscription ID */
                esc_html__(' - Subscription %s - ', 'stellarpay'),
                "#$subscriptionId"
            );
        }

        $order->add_order_note(
            sprintf(
                /* translators: 1. Refund type, 2. amount (formatted), 3. Refunded by `admin`, `webhook`, etc; 4. Subscription ID message, 5. the Stripe refund id, 6. refund message */
                esc_html__('%1$s %2$s via %3$s%4$sRefund ID: %5$s - Reason: %6$s', 'stellarpay'),
                $isPartialRefund ? esc_html__('Partial refunded', 'stellarpay') : esc_html__('Refunded', 'stellarpay'),
                wc_price($refundedAmount->getAmount(), ['currency' => $order->get_currency()]),
                $refundedBy,
                $subscriptionIdMessage,
                $stripeRefundId,
                $reason ?: esc_html__('Other', 'stellarpay'),
            )
        );

        return $newRefund;
    }
}
