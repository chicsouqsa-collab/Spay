<?php

/**
 * This class is a model contract for WooCommerce product of "Variable" type.
 * This class should be extended by all StellarPay product types that support subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Simple
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits\WooCommerceProductClassUtilities;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable\Traits\SubscriptionVariableProductUtilities;
use WC_Product_Variable;

/**
 * @since 1.8.0
 */
abstract class VariableProduct extends WC_Product_Variable implements SubscriptionProduct
{
    use SubscriptionVariableProductUtilities;
    use WooCommerceProductClassUtilities;

    /**
     * @since 1.8.0
     * @return SubscriptionProductType
     */
    abstract public function getProductType(): SubscriptionProductType;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function get_price_html($deprecated = ''): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->hasVariationsSameData()) {
            return sprintf(
                // translators: %1$s is the amount, %2$s is the billing period. Example: `From: $10 / 3 months`.
                _x('%1$s / %2$s', 'Subscription product price', 'stellarpay'),
                wc_price((float)$this->getAmount()),
                $this->getFormattedBillingPeriod()
            );
        }

        return sprintf(
            // translators: %1$s is the amount, %2$s is the billing period. Example: `From: $10 / 3 months`.
            _x('From: %1$s / %2$s', 'Subscription product price', 'stellarpay'),
            wc_price((float)$this->getAmount()),
            $this->getFormattedBillingPeriod()
        );
    }
}
