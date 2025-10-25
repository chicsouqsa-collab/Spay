<?php

/**
 * This class manages daily tasks like webhook event data cleanup
 *
 * Note: a task should be small enough to fit like a single database query for data deletion,
 *       otherwise it is advisable to use a separate async job.
 *
 * @package StellarPay\Integrations\ActionScheduler
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler\Jobs;

use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\ActionScheduler\ActionScheduler;
use StellarPay\Integrations\ActionScheduler\DataTransferObjects\ActionSchedulerJobDTO;

/**
 * @since 1.3.0
 */
class DailyRecurringJob
{
    /**
     * @since 1.3.0
     */
    public const NAME = Constants::PLUGIN_SLUG . '_daily_job';

    /**
     * @since 1.3.0
     */
    public function __invoke(): void
    {
        if (ActionScheduler::hasScheduledAction(self::NAME)) {
            return;
        }

        $jobDTO = ActionSchedulerJobDTO::fromEventData([
            'hook-name' => self::NAME,
            'timestamp' => strtotime('midnight'),
            'unique' => true,
            'interval' => DAY_IN_SECONDS
        ]);

        ActionScheduler::scheduleRecurringAction($jobDTO);
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public static function registerTask(string $className): void
    {
        Hooks::addAction(self::NAME, $className);
    }
}
