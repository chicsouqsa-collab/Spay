<?php

/**
 * OrderUtilities
 *
 * This trait is responsible for providing utilities for order related operations.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Traits;

use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use WC_Order;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;

use function StellarPay\Core\container;

/**
 * Trait OrderUtilities
 *
 * @since 1.7.0
 */
trait OrderUtilities
{
    /**
     * Validate if the order is a test order and below to payment gateway.
     *
     * @since 1.7.0
     */
    protected function validateTestOrder(WC_Order $order): bool
    {
        if (Constants::GATEWAY_ID !== $order->get_payment_method('edit')) {
            return false;
        }

        if (! container(OrderRepository::class)->isTestOrder($order)) {
            return false;
        }

        return true;
    }

    /**
     * Get the order status label.
     *
     * @since 1.9.0
     */
    protected function getOrderStatusLabel(WC_Order $order): string
    {
        // Gracefully handle legacy statuses.
        if (in_array($order->get_status(), ['trash', 'draft', 'auto-draft'], true)) {
            return (get_post_status_object($order->get_status()))->label;
        }

        return wc_get_order_status_name($order->get_status());
    }
}
