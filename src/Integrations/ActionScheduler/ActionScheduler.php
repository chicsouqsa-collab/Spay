<?php

/**
 * This class use to access action scheduler library feature.
 *
 * @link https://actionscheduler.org/
 * @package StellarPay\Integrations\ActionScheduler
 * @since 1.2.0
 *
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler;

use StellarPay\Integrations\ActionScheduler\DataTransferObjects\ActionSchedulerJobDTO;

/**
 * @since 1.2.0
 */
class ActionScheduler
{
    /**
     * @since 1.2.0
     */
    public static function scheduleAsyncAction(ActionSchedulerJobDTO $jobDTO): int
    {
        return as_enqueue_async_action(
            $jobDTO->getHookName(),
            $jobDTO->getArguments(),
            $jobDTO->getGroupName(),
            $jobDTO->getUnique(),
            $jobDTO->getPriority()
        );
    }

    /**
     * @since 1.2.0
     */
    public static function scheduleRecurringAction(ActionSchedulerJobDTO $jobDTO): int
    {
        return as_schedule_recurring_action(
            $jobDTO->getTimestamp(),
            $jobDTO->getInterval(),
            $jobDTO->getHookName(),
            $jobDTO->getArguments(),
            $jobDTO->getGroupName(),
            $jobDTO->getUnique(),
            $jobDTO->getPriority()
        );
    }

    /**
     * @since 1.3.0
     */
    public static function scheduleSingleAction(ActionSchedulerJobDTO $jobDTO): int
    {
        return as_schedule_single_action(
            $jobDTO->getTimestamp(),
            $jobDTO->getHookName(),
            $jobDTO->getArguments(),
            $jobDTO->getGroupName(),
            $jobDTO->getUnique(),
            $jobDTO->getPriority()
        );
    }

    /**
     * @since 1.2.0
     */
    public static function unscheduleAction(string $actionId, array $args = []): ?int
    {
        return as_unschedule_action($actionId, $args);
    }

    /**
     * @since 1.9.0 Pass empty array if $args is "null"
     * @since 1.2.0
     */
    public static function unscheduleAllAction(string $actionId, ?array $args = null, string $group = ''): void
    {
        as_unschedule_all_actions($actionId, $args, $group);
    }

    /**
     * @since 1.9.0 Pass empty array if $args is "null"
     * @since 1.2.0
     */
    public static function hasScheduledAction(string $actionId, ?array $args = null, string $group = ''): bool
    {
        return as_has_scheduled_action($actionId, $args, $group);
    }
}
