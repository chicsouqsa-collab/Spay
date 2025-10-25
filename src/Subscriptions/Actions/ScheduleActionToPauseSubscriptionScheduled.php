<?php

/**
 * This class is used to schedule an action to pause a subscription at period end.
 *
 * @package StellarPay\Subscriptions\Actions
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Actions;

use StellarPay\Integrations\ActionScheduler\Jobs\SubscriptionPauseJob;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Subscriptions\Models\Subscription;

use function StellarPay\Core\container;

/**
 * Class ScheduleActionToPauseSubscriptionScheduled
 *
 * @since 1.9.0
 */
class ScheduleActionToPauseSubscriptionScheduled
{
    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    public function __invoke(Subscription $subscription): void
    {
        if ($subscription->status->equals(SubscriptionStatus::PAUSED())) {
            return;
        }

        $timestamp = $subscription->nextBillingAt->getTimestamp();

        $arguments = [
            'subscriptionId' => $subscription->id,
            'attempt' => 1
        ];

        container(SubscriptionPauseJob::class)->scheduleJob($timestamp, $arguments, $subscription);
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    public function unscheduleJob(Subscription $subscription): void
    {
        container(SubscriptionPauseJob::class)->unscheduleJob($subscription);
    }
}
