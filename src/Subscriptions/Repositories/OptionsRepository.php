<?php

/**
 * This class used ot manage subscription related options.
 *
 * @package StellarPay\Subscriptions\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Repositories;

use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.0.0
 */
class OptionsRepository
{
    /**
     * @since 1.0.0
     */
    public static function setTableVersion(string $version): bool
    {
        return update_option(Subscription::getTableName() . '_table_version', $version, false);
    }

    /**
     * @since 1.0.0
     */
    public static function setMetaTableVersion(string $version): bool
    {
        return update_option(Subscription::getMetaTableName() . '_table_version', $version, false);
    }
}
