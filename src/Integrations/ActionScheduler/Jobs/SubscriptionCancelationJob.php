<?php

/**
 * This class is responsible for registering the job to cancel an expired subscription.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler\Jobs;

use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\ActionScheduler\ActionScheduler;
use StellarPay\Integrations\ActionScheduler\DataTransferObjects\ActionSchedulerJobDTO;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\Subscriptions\Models\Subscription;

/**
  * @since 1.3.0
  */
class SubscriptionCancelationJob
{
    /**
      * @since 1.3.0
      * @throws BindingResolutionException|Exception
      */
    public function __invoke(int $subscriptionId, int $attempt): void
    {
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription instanceof Subscription) {
            return;
        }

        if ($subscription->cancel(true)) {
            OrderNote::onSubscriptionStatusChange($subscription, ModifierContextType::SYSTEM());
            return;
        }

        if ($attempt > 2) {
            return;
        }

        // Reschedule job.
        $now = Temporal::getCurrentDateTime();
        $timestamp = $now->getTimestamp() + 5 * MINUTE_IN_SECONDS;
        $arguments = ['subscriptionId' => $subscription->id, 'attempt' => $attempt + 1];

        $this->scheduleJob($timestamp, $arguments);
    }

    /**
     * @since 1.3.0
     */
    private function getJob(int $timestamp, array $arguments): ActionSchedulerJobDTO
    {
        return ActionSchedulerJobDTO::fromEventData(
            [
                'hook-name' => self::getActionSchedulerJobName(),
                'timestamp' => $timestamp,
                'arguments' => $arguments
            ],
        );
    }

    /**
     * @since 1.3.0
     */
    public function scheduleJob(int $timestamp, array $arguments): void
    {
        ActionScheduler::scheduleSingleAction($this->getJob($timestamp, $arguments));
    }

    /**
     * @since 1.3.0
     */
    public static function getActionSchedulerJobName(): string
    {
        return Constants::PLUGIN_SLUG . '_cancel_subscription';
    }
}
