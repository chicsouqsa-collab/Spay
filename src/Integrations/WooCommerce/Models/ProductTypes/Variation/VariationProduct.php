<?php

/**
 * This class is a model contract for WooCommerce product of "Variation" type.
 * This class should be extended by all StellarPay product types that support subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation;

use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits\SubscriptionProductUtilities;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits\WooCommerceProductClassUtilities;
use WC_Product_Variation;

/**
 * @since 1.8.0
 */
abstract class VariationProduct extends WC_Product_Variation implements SubscriptionProduct
{
    use SubscriptionProductUtilities;
    use WooCommerceProductClassUtilities;

    /**
     * @since 1.8.0
     */
    abstract public function getProductType(): SubscriptionProductType;
}
