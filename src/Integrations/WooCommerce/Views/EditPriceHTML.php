<?php

/**
 * This clas is responsible to provide a view for price for subscription product.
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
class EditPriceHTML
{
    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(string $priceHTML, WC_Product $product): string
    {
        if ($subscriptionProduct = ProductFactory::makeFromProduct($product)) {
            return $subscriptionProduct->get_price_html();
        }

        return $priceHTML;
    }
}
