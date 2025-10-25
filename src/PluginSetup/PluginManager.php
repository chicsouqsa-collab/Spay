<?php

/**
 * This class is used to manage the plugin activation, deactivation, and redirection on plugin activation.
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Core\Constants;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

use function StellarPay\Core\dbOptionKeyGenerator;

/**
* Class PluginManager
 *
 * @since 1.0.0
 */
class PluginManager
{
    /**
     * @since 1.0.0
     */
    public const OPTION_NAME_PLUGIN_PERMALINK_FLUSHED = Constants::PLUGIN_SLUG . '_plugin_permalinks_flushed';

    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    /**
     * This is used to manage the plugin activation.
     *
     * @since 1.0.0
     *
     */
    public static function activate(): void
    {
        // This option is used to trigger redirect to the getting-started page when the plugin is activated.
        update_option(dbOptionKeyGenerator('just_activated'), Constants::VERSION, false);

        self::unpauseFlushPermalinks();
    }

    /**
     * This is used to manage the plugin deactivation.
     * @since 1.0.0
     */
    public static function deactivate(): void
    {
        self::unpauseFlushPermalinks();
    }

    /**
     * @since 1.0.0
     */
    public function pluginActivationRedirect(): void
    {
        $optionName = dbOptionKeyGenerator('just_activated');

        if ($this->accountRepository->isLiveModeConnected()) {
            return;
        }

        if (get_option($optionName, false)) {
            delete_option($optionName);

            // Ensure this is not an AJAX request and that the user has the capability to manage options
            if (! defined('DOING_AJAX') && current_user_can('manage_options')) {
                $redirectUrl = sprintf(
                    admin_url('admin.php?page=%1$s#/getting-started'),
                    Constants::PLUGIN_SLUG
                );

                wp_safe_redirect(esc_url($redirectUrl));
                exit;
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public static function unpauseFlushPermalinks(): bool
    {
        return update_option(self::OPTION_NAME_PLUGIN_PERMALINK_FLUSHED, 0);
    }

    /**
     * @since 1.0.0
     */
    public static function pauseFlushPermalinks(): bool
    {
        return update_option(PluginManager::OPTION_NAME_PLUGIN_PERMALINK_FLUSHED, 1);
    }

    /**
     * @since 1.0.0
     */
    public static function canFlushPermalinks(): bool
    {
        $result = (int) get_option(self::OPTION_NAME_PLUGIN_PERMALINK_FLUSHED, 0);

        return 0 === $result;
    }
}
