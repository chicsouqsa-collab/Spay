<?php

/**
 * Handles the synchronization of the regular and sale prices of WooCommerce simple products
 * with StellarPay settings whenever changes are made via the quick edit feature.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use WC_Product;

/**
 * @since 1.8.0
 */
class SyncSimpleProductQuickEditChanges
{
    /**
     * @since 1.8.0
     */
    protected ProductRepository $productRepository;

    /**
     * @since 1.8.0
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __invoke(WC_Product $wcProduct): void
    {
        if (! $wcProduct->is_type('simple')) {
            return;
        }

        $product = ProductFactory::makeFromProduct($wcProduct);

        if (! $product instanceof SubscriptionProduct) {
            return;
        }

        $regularAmount = $wcProduct->get_regular_price();
        $saleAmount = $wcProduct->get_sale_price();

        if ($regularAmount !== $product->getRegularAmount('edit')) {
            $this->productRepository->saveRegularAmount($product, $regularAmount);
        }

        if ($saleAmount !== $product->getSaleAmount('edit')) {
            $this->productRepository->saveSaleAmount($product, $saleAmount);
        }
    }
}
