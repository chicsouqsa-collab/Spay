<?php

/**
 * This class is used to schedule an action to cancel a expired subscription scheduled.
 *
 * @package StellarPay\Subscriptions\Actions
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Actions;

use StellarPay\Integrations\ActionScheduler\Jobs\SubscriptionCancelationJob;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Subscriptions\Models\Subscription;

use function StellarPay\Core\container;

/**
 * Class ScheduleActionToCancelSubscriptionScheduled
 *
 * @since 1.3.0
 */
class ScheduleActionToCancelSubscriptionScheduled
{
    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function __invoke(Subscription $subscription): void
    {
        if (!$subscription->isScheduleType()) {
            return;
        }

        $timestamp = $subscription->expiresAt->getTimestamp();
        $arguments = [
            'subscriptionId' => $subscription->id,
            'attempt' => 1
        ];

        container(SubscriptionCancelationJob::class)->scheduleJob($timestamp, $arguments);
    }
}
