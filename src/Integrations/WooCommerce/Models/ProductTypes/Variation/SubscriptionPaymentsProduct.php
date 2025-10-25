<?php

/**
 * This class is a model for WooCommerce product of "SubscriptionPayments" type.
 *
 * @package StellarPay\Integrations\WooCommerce\Models
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation;

use StellarPay\Core\ValueObjects\SubscriptionProductType;

/**
 * @since 1.8.0
 */
class SubscriptionPaymentsProduct extends VariationProduct
{
    /**
     * @since 1.8.0
     * @return SubscriptionProductType
     */
    public function getProductType(): SubscriptionProductType
    {
        return SubscriptionProductType::SUBSCRIPTION_PAYMENTS();
    }
}
