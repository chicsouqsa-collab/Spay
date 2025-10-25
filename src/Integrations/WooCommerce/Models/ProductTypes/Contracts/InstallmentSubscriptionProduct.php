<?php

/**
 * Installment Subscription Product
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts;

/**
 * @since 1.9.0
 */
interface InstallmentSubscriptionProduct extends SubscriptionProduct
{
    /**
     * @since 1.9.0
     */
    public function getNumberOfPayments($context = 'view'): int;
}
