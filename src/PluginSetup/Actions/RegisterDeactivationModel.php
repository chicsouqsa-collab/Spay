<?php

/**
 * This class uses to register deactivation model, loading related assets and process deactivation request.
 *
 * @package StellarPay\PluginSetup\Actions
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Actions;

use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Request;
use StellarPay\PluginSetup\Controllers\DeactivationController;
use StellarPay\PluginSetup\Views\DeactivationModal\DeactivationModal;

/**
 * @since 1.2.0
 */
class RegisterDeactivationModel
{
    /**
     * @since 1.2.0
     */
    protected Request $request;

    /**
     * @since 1.2.0
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        global $pagenow;

        if ('plugins.php' !== $pagenow) {
            return;
        }

        add_action('admin_enqueue_scripts', [$this, 'addDeactivationAssets']);
        Hooks::addAction('deactivated_plugin', DeactivationController::class);
        add_action('admin_footer', [$this, 'renderDeactivationModal']);
    }

    /**
     * @since 1.2.0
     */
    public function renderDeactivationModal(): void
    {
            (new DeactivationModal())->render();
    }

    /**
     * Add the assets for the Deactivation modal.
     *
     * @since 1.0.0
     *
     * @param string $hookSuffix The current admin page.
     *
     * @throws BindingResolutionException
     */
    public function addDeactivationAssets(string $hookSuffix): void
    {
        if ('plugins.php' !== $hookSuffix) {
            return;
        }

        $scriptId = 'stellarpay-plugin-deactivation';

        (new EnqueueScript($scriptId, "/build/$scriptId.js"))
            ->loadInFooter()
            ->loadStyle()
            ->enqueue();
    }
}
