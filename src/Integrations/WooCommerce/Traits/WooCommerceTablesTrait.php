<?php

/**
 * WooCommerce tables trait.
 *
 * This trait used to get the WooCommerce tables.
 *
 * @package StellarPay\Integrations\WooCommerce\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Traits;

/**
 * Trait UseWooCommerceTables
 *
 * @since 1.0.0
 */
trait WooCommerceTablesTrait
{
    /**
     * Get the WooCommerce order table name.
     *
     * @since 1.0.0
     */
    protected function getWooCommerceOrderTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wc_orders';
    }

    /**
     * Get the WooCommerce order meta-table name.
     *
     * @since 1.0.0
     */
    protected function getWooCommerceOrderMetaTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wc_orders_meta';
    }
}
