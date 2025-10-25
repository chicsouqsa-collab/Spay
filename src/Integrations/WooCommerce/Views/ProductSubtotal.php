<?php

/**
 * This clas is responsible to provide a view for price for product subtotal for subscription product.
 *
 * @package StellarPay\Integrations\WooCommerce\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use WC_Product;

/**
 * @since 1.0.0
 */
class ProductSubtotal
{
    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(string $productSubtotal, WC_Product $product): string
    {
        $product = ProductFactory::makeFromProduct($product);

        if (! $product) {
            return $productSubtotal;
        }

        $billingPeriod = $product->getFormattedBillingPeriod();

        return sprintf(
            // translators: %1$s - the product subtotal, %2$s - the billing period e.g. `monthly`.
            esc_html__('%1$s / %2$s', 'stellarpay'),
            $productSubtotal,
            $billingPeriod
        );
    }
}
