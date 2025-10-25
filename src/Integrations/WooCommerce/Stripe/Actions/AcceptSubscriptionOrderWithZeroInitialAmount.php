<?php

/**
 * This class uses to enable the StellaPay payment method on zero cart/order value checkout.
 * By default, the WooCommerce hides all payment gateways and order/cart does not need payment.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.5.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use WC_Cart;

/**
 * @since 1.9.0 Move function to "EditPaymentGatewaysAvailabilityOnCheckout" class.
 * @since 1.5.0
 */
class AcceptSubscriptionOrderWithZeroInitialAmount
{
    use SubscriptionUtilities;
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function cartNeedsPayment(bool $cartNeedsPayment, WC_Cart $cart): bool
    {
        if (
            ! $cartNeedsPayment
            && wc()->cart instanceof WC_Cart
            && ((float) $cart->get_total('edit') <= 0)
            && $this->cartContainsSubscription()
        ) {
            return true;
        }

        return $cartNeedsPayment;
    }

    /**
     * @since 1.9.0 Rename function and validate allowed order status.
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function zeroOrderValueNeedsPayment(bool $orderNeedsPayment, \WC_Order $order): bool
    {
        $allowedOrderStatuses = [OrderStatus::CHECKOUT_DRAFT, OrderStatus::PENDING, OrderStatus::FAILED];
        $hasAllowedOrderStatuses = in_array($order->get_status('edit'), $allowedOrderStatuses, true);

        if (
            ! $orderNeedsPayment
            && $hasAllowedOrderStatuses
            && wc()->cart instanceof WC_Cart
            && ( (float) $order->get_total('edit') <= 0)
            && $this->hasSubscriptionProduct($order)
        ) {
            return true;
        }

        return $orderNeedsPayment;
    }
}
