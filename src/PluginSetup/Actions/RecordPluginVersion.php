<?php

/**
 * This class uses to record the plugin version in a database.
 *
 * @package StellarPay\PluginSetup\Actions
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Actions;

use StellarPay\Core\Constants;

use function StellarPay\Core\dbOptionKeyGenerator;

/**
 * @since 1.2.0
 */
class RecordPluginVersion
{
    /**
     * @since 1.2.0
     */
    public function __invoke(): void
    {
        $previousVersionOptionName = dbOptionKeyGenerator('previous_version');
        $currentVersionOptionName = dbOptionKeyGenerator('current_version');
        $currentVersion = get_option($currentVersionOptionName, '');

        if (Constants::VERSION !== $currentVersion) {
            update_option($previousVersionOptionName, $currentVersion ?: '', false);
            update_option($currentVersionOptionName, Constants::VERSION, false);
        }
    }
}
