<?php

/**
 * This class is a model for WooCommerce product of "InstallmentPayments" type.
 *
 * @package StellarPay\Integrations\WooCommerce\Models
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\InstallmentSubscriptionProduct;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 */
class InstallmentPaymentsProduct extends VariationProduct implements InstallmentSubscriptionProduct
{
    /**
     * @since 1.8.0
     */
    public function getProductType(): SubscriptionProductType
    {
        return SubscriptionProductType::INSTALLMENT_PAYMENTS();
    }

    /**
     * @since 1.8.0
     */
    public function getNumberOfPayments($context = 'view'): int
    {
        $numberOfPayments = $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_numberOfPayments', true),
            true,
            $context
        );

        return absint($numberOfPayments);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getFormattedBillingPeriod(): string
    {
        $billingPeriod = parent::getFormattedBillingPeriod();

        return sprintf(
            // translators: 1: the billing period e.g. `week` 2: number of payments
            esc_html__('%1$s (%2$s total payments)', 'stellarpay'),
            $billingPeriod,
            $this->getNumberOfPayments('edit'),
        );
    }
}
