<?php

/**
 * This class is responsible for saving a product type on product metadata.
 *
 * Saving logic in this class does not depend upon the woocommerce product type.
 * The Setting is saved on the product metadata, if exists and valid.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use StellarPay\Integrations\WooCommerce\Controllers\Traits\SaveProductSettingsUtilities;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use WC_Product;

/**
 * @since 1.8.0
 */
class SaveProductSetting
{
    use SaveProductSettingsUtilities;

    /**
     * @since 1.8.0
     */
    public function __invoke(int $productId): void
    {
        if ($this->notUserAuthorized()) {
            return;
        }

        if ($this->notHaveSettings()) {
            return;
        }

        $settings = $this->getSettings();
        $productType = $settings['productType'];

        if ($this->notValidProductType($productType)) {
            return;
        }

        $product = wc_get_product($productId);

        if ($product instanceof WC_Product && $this->isWooProductTypeInAllowList($product)) {
            $product->update_meta_data(ProductRepository::getProductTypeKey(), $productType);
            $product->save();
        }
    }

    /**
     * @since 1.8.0
     */
    private function isWooProductTypeInAllowList(WC_Product $product): bool
    {
        return in_array($product->get_type(), ['simple', 'variable']);
    }
}
