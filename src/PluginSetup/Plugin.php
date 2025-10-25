<?php

/**
 * This class is used to manage the application features and make it available to the application.
 *
 * @package StellarPay\PluginSetup
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup;

use StellarPay\Core\Constants;
use StellarPay\Core\Contracts\ServiceProvider;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\PluginSetup\Actions\CreateDatabaseTable;
use StellarPay\PluginSetup\Actions\RecordPluginVersion;
use StellarPay\PluginSetup\Actions\RegisterDeactivationModel;
use StellarPay\PluginSetup\Migrations\StoreHomeUrlInOptionTable;
use StellarPay\Vendors\StellarWP\Validation\Config as StellarWPValidationConfig;
use StellarPay\Core\Request;
use StellarPay\Vendors\StellarWP\Models\Config as StellarWPModels;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;
use StellarPay\Vendors\StellarWP\AdminNotices\Contracts\NotificationsRegistrarInterface;
use StellarPay\Vendors\StellarWP\AdminNotices\NotificationsRegistrar;

use function StellarPay\Core\container;
use function StellarPay\Core\dbOptionKeyGenerator;

/**
 * Class Plugin
 *
 * @since 1.0.0
 */
class Plugin
{
    /**
     * This flag is used to check if the service providers have been loaded.
     *
     * @since 1.0.0
     */
    private bool $providersLoaded = false;

    /**
     * The Request class is used to manage the request data.
     * @since 1.0.0
     */
    protected Request $request;

    /**
     * This is a list of service providers that will be loaded into the application.
     *
     * @since 1.3.0 ActionScheduler service provider
     * @since 1.0.0
     */
    private array $serviceProviders = [
        \StellarPay\Core\ServiceProvider::class,
        \StellarPay\PaymentGateways\ServiceProvider::class,
        \StellarPay\AdminDashboard\ServiceProvider::class,
        \StellarPay\Integrations\WooCommerce\ServiceProvider::class,
        \StellarPay\Integrations\StellarCommerce\ServiceProvider::class,
        \StellarPay\Integrations\Stripe\ServiceProvider::class,
        \StellarPay\Subscriptions\ServiceProvider::class,
        \StellarPay\Integrations\ActionScheduler\ServiceProvider::class,
        \StellarPay\MigrationLog\ServiceProvider::class,
        \StellarPay\Webhook\ServiceProvider::class
    ];

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->request = container(Request::class);
    }

    /**
     * Bootstraps the StellarPay Plugin
     *
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->setupConstant();

        // Boostrap plugin.
        Hooks::addAction('plugins_loaded', self::class, 'init');

        // Setup language.
        // Language should load on the "init" hook.
        Hooks::addAction('init', self::class, 'setupLanguage');

        // Handle plugin activation and deactivation event.
        register_activation_hook(Constants::$PLUGIN_ROOT_FILE, [PluginManager::class, 'activate']);
        register_deactivation_hook(Constants::$PLUGIN_ROOT_FILE, [PluginManager::class, 'deactivate']);

        // This is used to redirect to the getting-started page when the plugin is activated.
        Hooks::addAction('admin_init', PluginManager::class, 'pluginActivationRedirect');

        // Add plugin meta
        Hooks::addFilter('plugin_row_meta', PluginMeta::class, 'addPluginRowMeta', 10, 2);
        Hooks::addFilter('plugin_action_links_' . Constants::$PLUGIN_ROOT_FILE_RELATIVE_PATH, PluginMeta::class, 'addPluginSettingsMeta');

        // Flush permalinks.
        // Custom permalinks mostly created on init at "10" priority.
        // We should flush rewrite urls after that.
        Hooks::addAction('init', self::class, 'flushRewriteRules', 15);

        // Register deactivation model.
        Hooks::addAction('admin_init', RegisterDeactivationModel::class);

        // Register notices.
        Hooks::addAction('admin_init', self::class, 'registerNotices');

        // Register database tables
        $this->createDatabaseTable();
    }

    /**
     * Initiate StellarPay when WordPress Initializes plugins.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function init(): void
    {
        /**
         * Fires before the StellarPay core is initialized.
         *
         * @since 1.0.0
         */
        do_action('before_stellarpay_init');

        $this->setConfigForStellarWPLibraries();
        $this->loadServiceProviders();
        $this->recordPluginVersion();

        $this->registerMigrations();

        /**
         * Fire the action after StellarPay core loads.
         *
         * @since 1.0.0
         *
         * @param self $instance Plugin class instance.
         *
         */
        do_action('stellarpay_init', $this);
    }

    /**
     * @since 1.0.0
     */
    public function flushRewriteRules(): void
    {
        if (PluginManager::canFlushPermalinks()) {
            flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
            PluginManager::pauseFlushPermalinks();
        }
    }

    /**
     * This function is used to set up language for the application.
     * @since 1.0.0
     */
    public function setupLanguage(): void
    {
        Language::load();
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function setConfigForStellarWPLibraries(): void
    {
        // StellarWP validation
        StellarWPValidationConfig::setServiceContainer(container());
        StellarWPValidationConfig::setHookPrefix('stellarpay');
        StellarWPValidationConfig::initialize();

        // StellarWP models
        StellarWPModels::setHookPrefix('stellarpay');

        container()->bind(NotificationsRegistrarInterface::class, function () {
            return new NotificationsRegistrar();
        });

        AdminNotices::setContainer(container());
        AdminNotices::initialize(
            Constants::PLUGIN_SLUG,
            Constants::$PLUGIN_URL . '/vendor/vendor-prefixed/stellarwp/admin-notices'
        );
    }

    /**
     * @since 1.9.0 Set function access to public
     * @since 1.0.1
     */
    public function registerNotices(): void
    {
        NoticeManager::onWooCommerceMissing();
    }

    /**
     * This function is used to load service providers.
     *
     * @since 1.0.0
     */
    private function loadServiceProviders(): void
    {
        if ($this->providersLoaded) {
            return;
        }

        $providers = [];

        foreach ($this->serviceProviders as $serviceProvider) {
            if (!is_subclass_of($serviceProvider, ServiceProvider::class)) {
                throw new InvalidArgumentException(
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    "$serviceProvider class must implement the ServiceProvider interface"
                );
            }

            /** @var ServiceProvider $serviceProvider */
            $serviceProvider = new $serviceProvider();

            $serviceProvider->register();

            $providers[] = $serviceProvider;
        }

        foreach ($providers as $serviceProvider) {
            $serviceProvider->boot();
        }

        $this->providersLoaded = true;
    }

    /**
     * This function is used to set up constants.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function setupConstant(): void
    {
        container()->singleton(Constants::class);

        // Set up the plugin constants.
        // Few constants, aka static properties, are set in the Constants class constructor.
        container(Constants::class);
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    private function recordPluginVersion(): void
    {
        $recordPluginVersion = container(RecordPluginVersion::class);
        $recordPluginVersion();
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    private function createDatabaseTable(): void
    {
        $actionPrefixes = [
            'add_option_',
            'update_option_',
        ];

        foreach ($actionPrefixes as $actionPrefix) {
            $currentPluginVersionHookName = $actionPrefix . dbOptionKeyGenerator('current_version');
            Hooks::addAction($currentPluginVersionHookName, CreateDatabaseTable::class);
        }
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    private function registerMigrations(): void
    {
        container(MigrationsRegister::class)->addMigrations([
            StoreHomeUrlInOptionTable::class
        ]);
    }
}
