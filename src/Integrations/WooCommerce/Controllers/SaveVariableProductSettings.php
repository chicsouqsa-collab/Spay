<?php

/**
 * This class is responsible for saving variable product metadata.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\Request;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Controllers\Traits\SaveProductSettingsUtilities;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;
use WC_Product_Variable;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
class SaveVariableProductSettings
{
    use SaveProductSettingsUtilities;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        if ($this->notUserAuthorized()) {
            return;
        }

        if ($this->notHaveSettings()) {
            return;
        }

        $product = wc_get_product(absint(Request::post('product_id')));
        if (! $product instanceof WC_Product_Variable) {
            return;
        }

        $settings = $this->getSettings();
        $productType = $settings['productType'];

        if ($this->notValidProductType($productType)) {
            return;
        }

        $product->update_meta_data(ProductRepository::getProductTypeKey(), $productType);
        $product->save();

        add_action(
            'woocommerce_ajax_save_product_variations',
            static function () use ($product, $productType) {
                $minVariationData = container(ProductVariableRepository::class)
                    ->getMinVariationData($product, SubscriptionProductType::from($productType));

                $product->update_meta_data(ProductVariableRepository::getMinVariationDataKey(), $minVariationData);
                $product->save();
            }
        );
    }
}
