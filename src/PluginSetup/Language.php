<?php

/**
 * Language setup.
 *
 * This class is used to manage the application language.
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Core\Constants;

/**
 * Class Language.
 *
 * @since 1.0.0
 */
class Language
{
    /**
     * @since 1.0.0
     */
    public static function load(): void
    {
        $pluginRelativePath = self::getRelativePath();

        $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        // Traditional WordPress plugin locale filter.
        $locale = apply_filters('plugin_locale', $locale, Constants::TEXT_DOMAIN);

        // Setup paths to current locale file.
        $moFile = sprintf('%1$s-%2$s.mo', Constants::TEXT_DOMAIN, $locale);
        $moFileLocal = trailingslashit(WP_PLUGIN_DIR) . $pluginRelativePath . $moFile;
        $moFileGlobal = trailingslashit(WP_LANG_DIR) . 'plugins/' . $moFile;

        unload_textdomain(Constants::TEXT_DOMAIN);
        if (file_exists($moFileGlobal)) {
            // Look in global /wp-content/languages/plugins folder.
            load_textdomain(Constants::TEXT_DOMAIN, $moFileGlobal);
        } elseif (file_exists($moFileLocal)) {
            // Look in local /wp-content/plugins/stellarpay/languages/ folder.
            load_textdomain(Constants::TEXT_DOMAIN, $moFileLocal);
        } else {
            // Load the default language files.
            load_plugin_textdomain(Constants::TEXT_DOMAIN, false, $pluginRelativePath);
        }
    }

    /**
     * Return the plugin language dir relative path, e.g. "stellarpay/languages/"
     *
     * @since 1.0.0
     */
    public static function getRelativePath(): string
    {
        $pluginRelativePath = dirname(plugin_basename(Constants::$PLUGIN_ROOT_FILE)) . '/languages/';
        $pluginRelativePath = ltrim(apply_filters('stellarpay_languages_directory', $pluginRelativePath), '/\\');

        return trailingslashit($pluginRelativePath);
    }
}
