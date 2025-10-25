<?php

/**
 * This class is a trait for StellarPay variable product type that support subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits\AbstractSubscriptionProductUtilities;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;
use StellarPay\Vendors\Stripe\Exception\BadMethodCallException;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
trait SubscriptionVariableProductUtilities
{
    use AbstractSubscriptionProductUtilities;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getAmount($context = 'view'): string
    {
        return container(ProductVariableRepository::class)->getVariableAmount($this, $context);
    }

    /**
     * @since 1.8.0
     * @throws BadMethodCallException
     */
    public function getRegularAmount($context = 'view'): string
    {
        throw new BadMethodCallException('You should call variation product type to get regular amount.');
    }

    /**
     * @since 1.8.0
     * @throws BadMethodCallException
     */
    public function getSaleAmount($context = 'view'): string
    {
        throw new BadMethodCallException('You should call variation product type to get sale amount.');
    }

    /**
     * @since 1.8.0
     * @throws BadMethodCallException
     */
    public function getSaleFromDate($context = 'view'): ?\DateTimeImmutable
    {
        throw new BadMethodCallException('You should call variation product type to get sale from date.');
    }

    /**
     * @since 1.8.0.
     * @throws BadMethodCallException
     */
    public function getSaleToDate($context = 'view'): ?\DateTimeImmutable
    {
        throw new BadMethodCallException('You should call variation product type to get sale to date.');
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getBillingPeriod($context = 'view'): string
    {
        return container(ProductVariableRepository::class)->getVariableBillingPeriod($this, $context);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getRecurringPeriod($context = 'view'): string
    {
        return container(ProductVariableRepository::class)->getVariableRecurringPeriod($this, $context);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getRecurringFrequency($context = 'view'): int
    {
        return container(ProductVariableRepository::class)->getVariableRecurringFrequency($this, $context);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    private function hasVariationsSameData(): bool
    {
        return container(ProductVariableRepository::class)->hasVariationsSameData($this);
    }

    /**
     * @since 1.8.0
     * @throws BadMethodCallException
     */
    public function isOnSale(): bool
    {
        throw new BadMethodCallException('You should call variation product type to check if it is on sale.');
    }
}
