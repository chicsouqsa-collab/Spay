<?php

/**
 * @package StellarPay\MigrationLog
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\MigrationLog\Migrations\CreateMigrationLogTable;

use function StellarPay\Core\container;

/**
 * @since 1.2.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(MigrationLogRepository::class);
    }

    /**
     * @inheritdoc
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        container(MigrationsRegister::class)->addMigration(CreateMigrationLogTable::class);
    }
}
