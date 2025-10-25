<?php

/**
 * @package StellarPay\PluginSetup\Controllers
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Controllers;

use StellarPay\Core\Constants;
use StellarPay\Core\Contracts\Controller;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.2.0
 */
class DeactivationController extends Controller
{
    /**
     * Handle the plugin deactivation.
     *
     * This function updates the option `delete_all_data_on_delete`
     * when the plugin is deactivated.
     *
     * @param string $plugin Path to the plugin file relative to the plugin's directory.
     *
     * @since 1.0.0
     */
    public function __invoke(string $plugin)
    {
        if (Constants::$PLUGIN_ROOT_FILE_RELATIVE_PATH !== $plugin) {
            return;
        }

        if (!$this->request->hasPermission('deactivate_plugin')) {
            return;
        }

        if (isset($_POST['checked'])) {
            // Bulk action.
            check_admin_referer('bulk-plugins');
        } else {
            // Individual plugin deactivation.
            check_admin_referer('deactivate-plugin_' . $plugin);
        }

        $deleteAllOptionKey = dbMetaKeyGenerator('delete_all_data_on_delete', true);

        update_option(
            $deleteAllOptionKey,
            ! empty($this->request->get('delete_all_stellarpay_data')),
            false
        );
    }
}
