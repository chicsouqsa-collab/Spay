<?php

/**
 * @package StellarPay\Integrations\ActionScheduler
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Integrations\ActionScheduler\Jobs\DailyRecurringJob;
use StellarPay\Integrations\ActionScheduler\Jobs\SubscriptionCancelationJob;
use StellarPay\Integrations\ActionScheduler\Jobs\SubscriptionPauseJob;
use StellarPay\Integrations\ActionScheduler\Migrations\RegisterDailyRecurringJob;

use function StellarPay\Core\container;

/**
 * @since 1.3.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(ActionScheduler::class);
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        container(MigrationsRegister::class)->addMigration(RegisterDailyRecurringJob::class);

        // It is possible that action scheduler exit without creating a new job for a recurring job.
        // This works as a health checker and creates jobs only when necessary.
        Hooks::addAction('admin_init', DailyRecurringJob::class);


        Hooks::addAction(SubscriptionCancelationJob::getActionSchedulerJobName(), SubscriptionCancelationJob::class, '__invoke', 10, 2);
        Hooks::addAction(SubscriptionPauseJob::getActionSchedulerJobName(), SubscriptionPauseJob::class, '__invoke', 10, 2);
    }
}
