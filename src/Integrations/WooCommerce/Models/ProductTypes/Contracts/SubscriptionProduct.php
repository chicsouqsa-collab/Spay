<?php

/**
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts;

use StellarPay\Core\ValueObjects\SubscriptionPeriod;

/**
 * @since 1.8.0
 */
interface SubscriptionProduct extends Product
{
    /**
     * @since 1.8.0
     */
    public function getPeriod(): SubscriptionPeriod;

    /**
     * @since 1.8.0
     */
    public function getBillingPeriod($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    public function getFrequency(): int;

    /**
     * @since 1.8.0
     */
    public function getRecurringPeriod($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    public function getRecurringFrequency($context = 'view'): int;

    /**
     * @since 1.3.0 Use renamed function.
     * @since 1.8.0
     */
    public function getFormattedBillingPeriod(): string;
}
