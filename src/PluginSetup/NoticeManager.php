<?php

/**
 * This class use to manage notice related to plugin setup
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.1
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;

use function StellarPay\Core\prefixedKey;

/**
 * @since 1.6.0 Add static method to get SSL required notice.
 * @since 1.0.1
 */
class NoticeManager
{
    /**
     * @since 1.6.0 Use static method to get notice.
     * @since 1.0.1
     */
    public static function onWooCommerceMissing(): void
    {
        if (Environment::hasMinimumWooCommerceVersion()) {
            return;
        }

        AdminNotices::show(
            prefixedKey('woocommerce-required-notice'),
            self::getWooCommerceMissingNotice(),
        )->autoParagraph()
            ->asError()
            ->ifUserCan('manage_options');
    }

    /**
     * @since 1.6.0
     */
    public static function getWooCommerceMissingNotice(): string
    {
        return sprintf(
            /* translators: 1: Minimum WooCommerce version */
            esc_html__('StellarPay requires WooCommerce %1$s or higher to be installed and activated.', 'stellarpay'),
            Environment::minimumWooCommerceVersion()
        );
    }
}
