<?php

/**
 * ChargeRefundUpdated event processor.
 *
 * This class is responsible for processing the charge.refund.updated event.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe/Webhook/Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use Exception;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderRefundEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\RefundEventDTO;
use WC_Order;
use WC_Order_Refund;

/**
 * Class ChargeRefundUpdated
 *
 * @since 1.0.0
 */
class ChargeRefundUpdated extends OrderRefundEventProcessor
{
    /**
     * @since 1.0.0
    *
    * @throws Exception
     */
    protected function processOrderRefund(
        WC_Order $order,
        RefundEventDTO $event,
        WC_Order_Refund $refund = null
    ): ?WC_Order_Refund {
        // Refund should exist to apply updates.
        $refund = $this->getOrderRefund($order, $event->getRefundId());
        if (!$refund) {
            return null;
        }

        if ($event->isFailed() || $event->isCanceled()) {
            $refund->delete(true);
            $this->addNotes($order, $event);
        }

        return $refund;
    }

    /**
     * Add notes to the order.
     *
     * @since 1.0.0
     */
    private function addNotes(WC_Order $order, RefundEventDTO $event): void
    {
        $stripeRefundId = $event->getRefundId();
        $refundedAmount = Money::fromMinorAmount($event->getRefundAmount(), $order->get_currency('edit'));

        $amount = wc_price(
            $refundedAmount->getAmount(),
            ['currency' => $order->get_currency()]
        );

        $suffixMessage = sprintf(
            /* translators: 1. Refund id 2. Refund failure code */
            esc_html__('Refund ID: %1$s - Reason: %2$s', 'stellarpay'),
            $stripeRefundId,
            $event->getRefundFailureReason()
        );

        if ($event->isFailed()) {
            $note = sprintf(
            /* translators: 1. Amount (with currency symbol) 2. Suffix message */
                esc_html__('Refund failed for %1$s - %2$s', 'stellarpay'),
                $amount,
                $suffixMessage
            );
        } else {
            $note = sprintf(
                /* translators: 1. Amount (with currency symbol) 2. Suffix message */
                esc_html__('Refund canceled for %1$s - %2$s', 'stellarpay'),
                $amount,
                $suffixMessage
            );
        }

        $order->add_order_note($note);
    }
}
