<?php

/**
 * This class uses to create database tables which are required for plugin.
 *
 * @package StellarPay\PluginSetup\Actions
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\MigrationLog\Migrations\CreateMigrationLogTable;
use StellarPay\Subscriptions\Migrations\CreateSubscriptionDatabaseTable;
use StellarPay\Subscriptions\Migrations\CreateSubscriptionMetaDatabaseTable;
use StellarPay\Webhook\Migrations\CreateWebhookEventsTable;

use function StellarPay\Core\container;

/**
 * @since 1.2.0
 * @template T of Migration
 */
class CreateDatabaseTable
{
    /**
     * @since 1.2.0
     * @var class-string<T>[]
     */
    private array $tables = [
        CreateMigrationLogTable::class,
        CreateSubscriptionDatabaseTable::class,
        CreateSubscriptionMetaDatabaseTable::class,
        CreateWebhookEventsTable::class
    ];

    /**
     * @since 1.2.0
     * @throws BindingResolutionException|DatabaseMigrationException
     */
    public function __invoke(): void
    {
        foreach ($this->tables as $table) {
                $tableMigration = container($table);
                $tableMigration->run();
        }
    }
}
