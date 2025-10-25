<?php

/**
 * This controller is used to save StellarPay product variation settings.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Controllers\Traits\SaveProductSettingsUtilities;
use WC_Product_Variation;

/**
 * @since 1.8.0
 */
class SaveProductVariationSettings
{
    use SaveProductSettingsUtilities;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     * @throws \Exception
     */
    public function __invoke(WC_Product_Variation $productVariation, int $i): void
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

        $options = $settings[$productType][$i] ?? null;

        if (!$options) {
            return;
        }

        // Save the product variation options.
        $this->save($productVariation, SubscriptionProductType::from($productType), $options);

        // Sync the amount value with the WooCommerce price fields.
        // This is required to make sure StellarPay subscription amount value should reflect in the Woocommerce as product price without using action and filter hooks.
        $this->syncAmountValueWithWooCommercePriceFields($productVariation);
    }
}
