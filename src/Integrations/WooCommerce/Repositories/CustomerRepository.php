<?php

/**
 * This class is used to handle customer data in the WooCommerce integration
 *
 * @package StellarPay\Integrations\WooCommerce\Repositories
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Repositories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.7.0
 */
class CustomerRepository
{
    /**
     * Check if a customer has StellarPay subscriptions.
     *
     * @since 1.7.0
     *
     * @param int $customerId The ID of the customer.
     *
     * @return bool True if the customer has StellarPay subscriptions, false otherwise.
     * @throws BindingResolutionException
     */
    public static function hasStellarPaySubscriptions(int $customerId): bool
    {
        // Replace the following line with the actual logic to check for StellarPay subscriptions.
        return Subscription::customerHasSubscriptions($customerId);
    }
}
