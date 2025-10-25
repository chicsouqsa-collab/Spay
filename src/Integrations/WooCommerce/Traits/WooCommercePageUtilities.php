<?php

/**
 * This trait provides functions for the WooCommerce related page.
 *
 * @package StellarPay\Integrations\WooCommerce\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Traits;

use WP_Post;

trait WooCommercePageUtilities
{
    /**
     * @since 1.8.0
     */
    protected function isBlockCartPage(): bool
    {
        global $post;
        return $post instanceof WP_Post && has_block('woocommerce/cart', $post);
    }

    /**
     * @since 1.8.0
     */
    protected function isBlockCheckoutPage(): bool
    {
        global $post;
        return $post instanceof WP_Post && has_block('woocommerce/checkout', $post);
    }
}
