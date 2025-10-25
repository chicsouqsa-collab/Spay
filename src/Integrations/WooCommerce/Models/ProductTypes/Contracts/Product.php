<?php

/**
 * This class is a model contract used for StellarPay product types.
 *
 * @package StellarPay\Integrations\WooCommerce\Models
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts;

use DateTimeImmutable;
use StellarPay\Core\ValueObjects\SubscriptionProductType;

/**
 * @since 1.8.0
 */
interface Product
{
    /**
     * @since 1.8.0
     */
    public function getAmount($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    public function getRegularAmount($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    public function getSaleAmount($context = 'view'): string;

    /**
     * @since 1.8.0
     * @return SubscriptionProductType
     */
    public function getProductType(): SubscriptionProductType;

    /**
     * @since 1.8.0
     */
    public function getSaleFromDate($context = 'view'): ?DateTimeImmutable;

    /**
     * @since 1.8.0
     */
    public function getSaleToDate($context = 'view'): ?DateTimeImmutable;

    /**
     * @since 1.8.0
     */
    public function isOnSale(): bool;
}
