<?php

/**
 * @package StellarPay\PluginSetup\Migrations
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Migrations;

use StellarPay\Core\Constants;
use StellarPay\Core\Migrations\Contracts\Migration;

/**
 * @since 1.3.0
 */
class StoreHomeUrlInOptionTable extends Migration
{
    /**
     * @since 1.3.0
     */
    public const OPTION_NAME = Constants::PLUGIN_SLUG . '_home_url';

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public function run(): void
    {
        update_option(self::OPTION_NAME, base64_encode(get_home_url()));
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function id(): string
    {
        return 'store-home-url-in-option-table';
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function title(): string
    {
        return esc_html__('Store Home URL in Option Table', 'stellarpay');
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function timestamp(): int
    {
        return strtotime('2025-01-23 11:22:00');
    }
}
