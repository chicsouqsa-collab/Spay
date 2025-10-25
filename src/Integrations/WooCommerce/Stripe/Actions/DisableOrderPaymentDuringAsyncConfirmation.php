<?php

/**
 * Disable order payment during async confirmation.
 * We confirm payment on webhook notification, we should disable order payment for a sparing interval.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use WC_Order;

/**
 * @since 1.9.0
 */
final class DisableOrderPaymentDuringAsyncConfirmation
{
    use WooCommercePaymentGatewayUtilities;


    /**
     * @since 1.9.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.9.0
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @since 1.9.0
     */
    public function maybeOrderNeedsPayment(bool $maybeOrderNeedsPayment, WC_Order $order): bool
    {
        // If the order is not in pending status, then return.
        if (OrderStatus::PENDING !== $order->get_status('edit')) {
            return $maybeOrderNeedsPayment;
        }

        if (! $this->matchPaymentGatewayInOrder($order)) {
            return $maybeOrderNeedsPayment;
        }

        if (! $order->get_transaction_id('edit')) {
            return $maybeOrderNeedsPayment;
        }

        return false;
    }

    /**
     * @since 1.9.0
     */
    public function maybeCancelableOrder(array $orderStatusesForCancel, WC_Order $order): array
    {
        // If the order is not in pending status, then return.
        if (OrderStatus::PENDING !== $order->get_status('edit')) {
            return $orderStatusesForCancel;
        }

        if (! $this->matchPaymentGatewayInOrder($order)) {
            return $orderStatusesForCancel;
        }

        if (! $order->get_transaction_id('edit')) {
            return $orderStatusesForCancel;
        }

        return [];
    }
}
