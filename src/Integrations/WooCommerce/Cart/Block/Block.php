<?php

/**
 * This class is responsible to register assets on the WooCommerce block checkout page.
 *
 * @package StellarPay\Integrations\WooCommerce\Cart\Block
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Cart\Block;

use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;

/**
 * @since 1.0.0
 */
class Block
{
    /**
     * Add assets
     *
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        global $post;

        $shouldEnqueue = has_block('woocommerce/checkout', $post) || has_block('woocommerce/cart', $post);

        if (! $shouldEnqueue) {
            return;
        }

        $scriptId = 'stellarpay-woocommerce-cart-block';

        (new EnqueueScript($scriptId, "/build/$scriptId.js"))
            ->loadStyle()
            ->loadInFooter()
            ->enqueue();
    }
}
