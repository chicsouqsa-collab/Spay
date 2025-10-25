<?php

/**
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable;

use StellarPay\Core\ValueObjects\SubscriptionProductType;

/**
 * @since 1.8.0
 */
class SubscriptionPaymentsProduct extends VariableProduct
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
