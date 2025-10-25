<?php

/**
 * This class is responsible to provide repository for product variation.
 *
 * @package StellarPay\Integrations\WooCommerce\Repositories
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Repositories;

use StellarPay\Core\ValueObjects\SubscriptionProductType;
use WC_Product;
use WC_Product_Variation;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 */
class ProductVariationRepository
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
     */
    public function getProductType(WC_Product_Variation $variation): ?SubscriptionProductType
    {
        $product = wc_get_product($variation->get_parent_id());

        if ($product instanceof WC_Product) {
            return $this->productRepository->getProductType($product);
        }

        return null;
    }

    /**
     * @since 1.8.0
     */
    public function hasCustomBillingPeriod(int $variationId, SubscriptionProductType $productType = null): bool
    {
        $variation = wc_get_product($variationId);

        if ($variation instanceof WC_Product_Variation) {
            $productType = $productType ?? $this->productRepository->getProductType($variation);
            $billingPeriod =  $variation->get_meta(dbMetaKeyGenerator("{$productType}_billingPeriod", true));

            return 'custom' === $billingPeriod;
        }

        return false;
    }
}
