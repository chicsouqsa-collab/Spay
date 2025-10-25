<?php

/**
 * This class provides server details like WordPress version, PHP version, etc.
 *
 * @package StellarPay\Core
 * @since 1.0.1
 */

declare(strict_types=1);

namespace StellarPay\Core;

/**
 * @since 1.0.1
 */
class ServerEnvironment
{
    /**
     * @since 1.0.1
     */
    public static function getWordPressVersion(): string
    {
        return get_bloginfo('version');
    }

    /**
     * @since 1.6.0
     */
    public static function getWooCommerceVersion(): string
    {
        return class_exists('WooCommerce') ? WC()->version : '0.0.0';
    }
}
