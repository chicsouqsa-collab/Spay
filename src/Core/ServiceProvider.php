<?php

/**
 * Service Provider
 *
 * @package StellarPay\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Core\Migrations\MigrationsRunner;

/**
 * Class ServiceProvider
 *
 * @package StellarPay\Core
 */
class ServiceProvider implements Contracts\ServiceProvider
{
    /**
     * This function registers the service provider.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(Cache::class);
        container()->singleton(DebugMode::class, function () {
            return DebugMode::makeWithWpDebugConstant();
        });

        container()->singleton(ShutdownScheduler::class, function () {
            $shutdownScheduler =  new ShutdownScheduler();

            register_shutdown_function([$shutdownScheduler, 'callRegisteredShutdown']);

            return $shutdownScheduler;
        });

        $this->registerMigrationService();
    }

    /**
     * This function boots the service provider.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Register customer rewrite rules.
        // This allows rendering the custom views or process business logic.
        Hooks::addAction('init', RoutesRegisterer::class);

        $this->bootMigrationService();
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    private function registerMigrationService(): void
    {
        container()->singleton(MigrationsRunner::class);
        container()->singleton(MigrationsRegister::class);
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    private function bootMigrationService(): void
    {
        Hooks::addAction('admin_init', MigrationsRunner::class, '__invoke', 0);
    }
}
