<?php

/**
 * This class is responsible to provide logics ro perform on the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Repositories;

use StellarPay\Core\ValueObjects\SubscriptionProductType;
use WC_Product;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.0.0
 */
class ProductRepository
{
    /**
     * @since 1.8.0 Update access to public
     * @since 1.0.0
     */
    public static function getProductTypeKey(): string
    {
        return dbMetaKeyGenerator('productType', true);
    }

    /**
     * @since 1.0.0
     */
    public function getProductType(WC_Product $product): ?SubscriptionProductType
    {
        $productType = $product->get_meta(self::getProductTypeKey());

        if (empty($productType)) {
            return null;
        }

        return new SubscriptionProductType($productType);
    }


    /**
     * @since 1.8.0
     */
    public function saveRegularAmount(WC_Product $product, string $amount): bool
    {
         $product->update_meta_data(
             dbMetaKeyGenerator($this->getProductType($product)->getValue() . '_amount', true),
             $amount
         );

         return (bool) $product->save();
    }

    /**
     * @since 1.8.0
     */
    public function saveSaleAmount(WC_Product $product, string $amount): bool
    {
        $product->update_meta_data(
            dbMetaKeyGenerator($this->getProductType($product)->getValue() . '_saleAmount', true),
            $amount
        );

        return (bool) $product->save();
    }
}
