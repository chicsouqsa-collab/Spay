<?php

/**
 * This controller is used to save StellarPay product settings.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Request;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Controllers\Traits\SaveProductSettingsUtilities;
use WC_Product;

/**
 * @since 1.8.0 Use "SaveProductSettingsUtilities" trait.
 * @since 1.0.0
 */
class SaveSimpleProductSettings
{
    use SaveProductSettingsUtilities;

    /**
     * @since 1.0.0
     */
    protected Request $request;

    /**
     * @since 1.0.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(int $productId): void
    {
        $product = wc_get_product($productId);

        if (
            ! $product instanceof WC_Product
            || ! $product->is_type('simple')
        ) {
            return;
        }

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

        // Check if the product type has any options to save.
        if (empty($settings[$productType])) {
            return;
        }

        // Save the product options.
        $options = $settings[$productType];
        $this->save($product, SubscriptionProductType::from($productType), $options);

        // Sync the amount value with the WooCommerce price fields.
        // This is required to make sure StellarPay subscription amount value should reflect in the Woocommerce as product price without using action and filter hooks.
        $this->syncAmountValueWithWooCommercePriceFields($product);
    }
}
