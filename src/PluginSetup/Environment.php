<?php

/**
 * This class is used to validate the plugin environment.
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Core\ServerEnvironment;
use StellarPay\PluginSetup\Migrations\StoreHomeUrlInOptionTable;
use WooCommerce;

/**
 * Class Environment
 *
 * @since 1.0.0
 */
class Environment
{
    /**
     * This is used to check if WooCommerce is active.
     *
     * @since 1.0.0
     */
    public static function isWoocommerceActive(): bool
    {
        return class_exists(WooCommerce::class);
    }

    /**
     * @since 1.3.0
     */
    public static function isWebsiteMigrated(): bool
    {
        $previousHomeUrl = get_option(StoreHomeUrlInOptionTable::OPTION_NAME, '');
        $currentHomeUrl = base64_encode(get_home_url());

        if (! $previousHomeUrl) {
            return false;
        }

        return $previousHomeUrl !== $currentHomeUrl;
    }

    /**
     * Check if the Woocommerce Subscription is active.
     *
     * @sxince 1.8.0
     */
    public static function isWooSubscriptionActive(): bool
    {
        return class_exists('WC_Subscriptions');
    }

    /**
     * @since 1.6.0
     */
    public static function minimumWooCommerceVersion(): string
    {
        return '8.0';
    }

    /**
     * @since 1.6.0
     */
    public static function hasMinimumWooCommerceVersion(): bool
    {
        return self::wooCommerceVersionCompare(self::minimumWooCommerceVersion(), '>=');
    }

    /**
     * @since 1.9.0
     */
    public static function wooCommerceVersionCompare(string $version, ?string $operator): bool
    {
        return self::isWoocommerceActive()
            && version_compare(
                ServerEnvironment::getWooCommerceVersion(),
                $version,
                $operator
            );
    }
}
