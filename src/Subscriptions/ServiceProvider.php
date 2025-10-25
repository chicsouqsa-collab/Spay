<?php

/**
 * ServiceProvider
 *
 * This file is used to bootstrap the plugin subscription feature.
 *
 * @package StellarPay\Subscriptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Subscriptions\Actions\ScheduleActionToCancelSubscriptionScheduled;
use StellarPay\Subscriptions\Migrations\AddAmountColumnToSubscriptionDatabaseTable;
use StellarPay\Subscriptions\Actions\ScheduleActionToPauseSubscriptionScheduled;
use StellarPay\Subscriptions\Migrations\AddExpiresAtColumnToSubscriptionsTable;
use StellarPay\Subscriptions\Migrations\AddResumedAtColumnsToSubscriptionTable;
use StellarPay\Subscriptions\Migrations\CreateSubscriptionDatabaseTable;
use StellarPay\Subscriptions\Migrations\CreateSubscriptionMetaDatabaseTable;
use StellarPay\Subscriptions\Repositories\SubscriptionMetaRepository;
use StellarPay\Subscriptions\Repositories\SubscriptionRepository;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @since 1.1.0 Register subscription repositories as singleton
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(SubscriptionMetaRepository::class);
        container()->singleton(SubscriptionRepository::class);
    }

    /**
     * @since 1.8.0 Register "AddAmountColumnToSubscriptionDatabaseTable" migration.
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        container(MigrationsRegister::class)->addMigrations([
            CreateSubscriptionDatabaseTable::class,
            CreateSubscriptionMetaDatabaseTable::class,
            AddExpiresAtColumnToSubscriptionsTable::class,
            AddAmountColumnToSubscriptionDatabaseTable::class,
            AddResumedAtColumnsToSubscriptionTable::class
        ]);

        Hooks::addAction('stellarpay_subscription_updated_to_cancel_at_period_end', ScheduleActionToCancelSubscriptionScheduled::class);
        Hooks::addAction('stellarpay_subscription_paused_at_period_end', ScheduleActionToPauseSubscriptionScheduled::class);
        Hooks::addAction('stellarpay_subscription_resumed', ScheduleActionToPauseSubscriptionScheduled::class, 'unscheduleJob');
        Hooks::addAction('stellarpay_subscription_canceled', ScheduleActionToPauseSubscriptionScheduled::class, 'unscheduleJob');
    }
}
