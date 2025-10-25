<?php

/**
 * This class is responsible for registering the job to pause a subscription at period end.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.9.0
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
use StellarPay\Subscriptions\Repositories\SubscriptionRepository;

/**
 * @since 1.9.0
 */
class SubscriptionPauseJob
{
    /**
     * @since 1.9.0
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @since 1.9.0
     */
    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException|Exception
     */
    public function __invoke(int $subscriptionId, int $attempt): void
    {
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription instanceof Subscription) {
            return;
        }

        if ($subscription->pause()) {
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

        $this->scheduleJob($timestamp, $arguments, $subscription);
    }

    /**
     * @since 1.9.0
     */
    private function getJob(int $timestamp, array $arguments, Subscription $subscription): ActionSchedulerJobDTO
    {
        return ActionSchedulerJobDTO::fromEventData(
            [
                'hook-name' => self::getActionSchedulerJobName(),
                'timestamp' => $timestamp,
                'arguments' => $arguments,
                'group-name' => (string) $subscription->id,
            ],
        );
    }

    /**
     * @since 1.9.0
     */
    public function scheduleJob(int $timestamp, array $arguments, Subscription $subscription): void
    {
        ActionScheduler::scheduleSingleAction($this->getJob($timestamp, $arguments, $subscription));
    }

    /**
     * @since 1.9.0
     */
    public function unscheduleJob(Subscription $subscription): void
    {
        ActionScheduler::unscheduleAllAction(self::getActionSchedulerJobName(), null, (string) $subscription->id);
    }

    /**
     * @since 1.9.0
     */
    public function hasScheduledJob(Subscription $subscription): bool
    {
        return ActionScheduler::hasScheduledAction(self::getActionSchedulerJobName(), null, (string) $subscription->id);
    }

    /**
     * @since 1.9.0
     */
    public static function getActionSchedulerJobName(): string
    {
        return Constants::PLUGIN_SLUG . '_pause_subscription';
    }
}
