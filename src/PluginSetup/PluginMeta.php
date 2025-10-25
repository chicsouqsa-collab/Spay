<?php

/**
 * This is used to add plugin row meta-links.
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Core\Constants;

/**
 * @since 1.0.0
 */
class PluginMeta
{
    /**
     * Adds a link in the wider column. Typically used to add docs and support plugin row meta-links.
     *
     * @since 1.0.0
     */
    public static function addPluginRowMeta(array $pluginMeta, string $pluginFile): array
    {
        if (Constants::$PLUGIN_ROOT_FILE_RELATIVE_PATH !== $pluginFile) {
            return $pluginMeta;
        }

        $newMetaLinks = [
            sprintf(
                '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url(
                    add_query_arg(
                        [
                            'utm_source'   => 'plugins-page',
                            'utm_medium'   => 'plugin-row',
                            'utm_campaign' => 'admin',
                        ],
                        'https://links.stellarwp.com/stellarpay/docs/'
                    )
                ),
                esc_html__('Documentation', 'stellarpay')
            ),
            sprintf(
                '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url(
                    add_query_arg(
                        [
                            'utm_source'   => 'plugins-page',
                            'utm_medium'   => 'plugin-row',
                            'utm_campaign' => 'admin',
                        ],
                        'https://links.stellarwp.com/stellarpay/support/'
                    )
                ),
                esc_html__('Support', 'stellarpay')
            ),
        ];

        return array_merge($pluginMeta, $newMetaLinks);
    }

    /**
     * Adds a settings link to the plugin row meta.
     *
     * @since 1.0.0
     */
    public static function addPluginSettingsMeta($actions): array
    {
        $newActions = [
            'settings' => sprintf(
                '<a href="%1$s">%2$s</a>',
                admin_url('admin.php?page=stellarpay#/settings'),
                esc_html__('Settings', 'stellarpay')
            ),
        ];

        return array_merge($newActions, $actions);
    }
}
