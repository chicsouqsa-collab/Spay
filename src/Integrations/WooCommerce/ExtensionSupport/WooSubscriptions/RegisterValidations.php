<?php

/**
 * This class use to register cart and checkout validations for mixed subscriptions.
 * Customer can subscribe to a single type of subscription product in one order.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions;

use StellarPay\Integrations\WooCommerce\Traits\MixedSubscriptionUtilities;
use WP_Error;

/**
 * @since 1.7.0
 */
class RegisterValidations
{
    use MixedSubscriptionUtilities;

    /**
     * @since 1.7.0
     */
    public function canAddToCart(bool $isValid, int $productId, int $quantity, $variationId = null): bool
    {
        if (empty(WC()->cart->get_cart_contents())) {
            return $isValid;
        }

        $productId = empty($variationId) ? $productId : $variationId;
        $product = wc_get_product($productId);

        if (!$product) {
            return $isValid;
        }

        if (!$this->hasAnotherSubscriptionTypeInTheCart($product)) {
            return $isValid;
        }

        wc_add_notice($this->getMixedSubscriptionsErrorMessage(), 'notice');

        return false;
    }

    /**
     * Checkout validation.
     *
     * This function can be hooked on `woocommerce_checkout_process` or
     * `woocommerce_store_api_cart_errors`.
     *
     * @since 1.7.0
     */
    public function validateCheckout($cartErrors = null): void
    {
        $hasMixedSubscriptionsInTheCart = $this->hasMixedSubscriptionsInTheCart();

        if (!$hasMixedSubscriptionsInTheCart) {
            return;
        }

        if (!is_a($cartErrors, WP_Error::class)) {
            wc_add_notice($this->getMixedSubscriptionsErrorMessage(), 'error');

            return;
        }

        $cartErrors->add('stellarpay_error', $this->getMixedSubscriptionsErrorMessage());
    }

    /**
     * @since 1.7.0
     */
    protected function getMixedSubscriptionsErrorMessage(): string
    {
        return esc_html__('Due to payment gateway restrictions, different subscription products can not be purchased at the same time.', 'stellarpay');
    }
}
