<?php

/**
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions;

use StellarPay\Core\Facades\Request;
use WC_Order;
use WC_Product;
use WC_Subscriptions_Product;
use WC_Order_Item_Product;
use StellarPay\Core\Facades\QueryVars;

use function wc_get_order;
use function wcs_is_subscription;

/**
 * @since 1.7.0
 */
trait WooSubscriptionUtilities
{
    /**
     * @since 1.7.0
     */
    protected function cartContainsWooSubscription(): bool
    {
        if (empty(WC()->cart->get_cart_contents())) {
            return false;
        }

        foreach (WC()->cart->get_cart_contents() as $cartItem) {
            $product = $cartItem['data'];

            if (! ($product instanceof WC_Product)) {
                continue;
            }

            if (! WC_Subscriptions_Product::is_subscription($product)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @since 1.7.0
     */
    protected function hasWooSubscriptionProduct(WC_Order $order): bool
    {
        foreach ($order->get_items() as $orderItem) {
            if (! $orderItem instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $orderItem->get_product();

            if (WC_Subscriptions_Product::is_subscription($product)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @since 1.7.0
     */
    protected function isWooSubscriptionUpdatePaymentMethodPage(): bool
    {
        if (!function_exists('wcs_is_subscription')) {
            return false;
        }

        // If the order-pay query var is missing or not an integer, return false.
        if (QueryVars::missing('order-pay') || QueryVars::getInt('order-pay') <= 0) {
            return false;
        }

        $payForOrder = Request::get('pay_for_order', '');
        $changePaymentMethod = Request::get('change_payment_method', '');
        $wpNonce = Request::get('_wpnonce', '');

        $result = $payForOrder && $changePaymentMethod && $wpNonce;

        // If the request is not valid, return false.
        if (! $result) {
            return false;
        }

        // Validate the order, whether it's a the WooCommerce Subscription or not.
        $orderId = QueryVars::getInt('order-pay');
        $order = wc_get_order($orderId);

        if (!$order || !wcs_is_subscription($order)) {
            return false;
        }

        return true;
    }
}
