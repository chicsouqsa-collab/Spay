<?php

/**
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\InstallmentSubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
class InstallmentPaymentsProduct extends VariableProduct implements InstallmentSubscriptionProduct
{
    /**
     * @since 1.8.0
     * @return SubscriptionProductType
     */
    public function getProductType(): SubscriptionProductType
    {
        return SubscriptionProductType::INSTALLMENT_PAYMENTS();
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getNumberOfPayments($context = 'view'): int
    {
        return container(ProductVariableRepository::class)->getVariableNumberOfPayments($this, $context);
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
