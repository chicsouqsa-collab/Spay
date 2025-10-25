<?php

/**
 * Helper functions for WooCommerce.
 *
 * @package StellarPay\Integrations\WooCommerce\Utils
 * @since 1.5.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Utils;

use WP_Post;

/**
 * @since 1.7.0 Make functions non-static and use class properties instead.
 * @since 1.5.0
 */
class PageType
{
    /**
     * @since 1.7.0
     */
    protected ?WP_Post $post;

    /**
     * @since 1.7.0
     */
    protected bool $isValidPostType;

    /**
     * @since 1.7.0
     */
    public function __construct()
    {
        global $post;

        $this->post = $post;
        $this->isValidPostType = $post instanceof WP_Post;
    }

    /**
     * @since 1.5.0
     */
    public function isClassicCheckout(): bool
    {
        return $this->isValidPostType
               && (
                   has_shortcode($this->post->post_content, 'woocommerce_checkout')
                   || has_block('woocommerce/classic-shortcode')
               );
    }

    /**
     * @since 1.5.0
     */
    public function isClassicCart(): bool
    {
        return $this->isValidPostType
               && (
                   has_shortcode($this->post->post_content, 'woocommerce_cart')
                   || has_block('woocommerce/classic-shortcode')
               );
    }
}
