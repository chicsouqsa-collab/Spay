<?php

/**
 * This class is responsible for resuming a Subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use DateTime;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\SubscriptionResumeDTO;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionService as BaseSubscriptionService;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\PaymentGateways\Stripe\ValueObjects\BillingCycleAnchor;
use StellarPay\PaymentGateways\Stripe\ValueObjects\ProrationBehavior;

/**
 * @since 1.9.0
 */
class SubscriptionService
{
    /**
     * @since 1.9.0
     */
    protected BaseSubscriptionService $baseSubscriptionService;

    /**
     * @since 1.9.0
     */
    public function __construct(BaseSubscriptionService $baseSubscriptionService)
    {
        $this->baseSubscriptionService = $baseSubscriptionService;
    }

    /**
     * Resume a subscription.
     *
     * @since 1.9.0
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function resume(Subscription $subscription): bool
    {
        $hasCyclePassed = $subscription->hasRenewalDatePassed();

        // If the billing cycle has passed, we need to resume the subscription right away and
        // set the next billing date to 1 interval from today.
        if ($hasCyclePassed) {
            $subscriptionResumeDTO = SubscriptionResumeDTO::fromArray([
                'stripeSubscriptionId' => $subscription->transactionId,
                'billingCycleAnchor' => BillingCycleAnchor::NOW(),
                'prorationBehavior' => ProrationBehavior::NONE(),
            ]);

            $subscriptionDTO = $this->baseSubscriptionService->resumeSubscription($subscriptionResumeDTO);
            $isResumed = $subscriptionDTO->isActive();

            if (! $isResumed) {
                throw new Exception('Subscription could not be resumed');
            }

            $nextBillingDate = $subscription->calculateNextBillingDateFromToday();
            $subscription->nextBillingAt = $nextBillingDate;
            $subscription->nextBillingAtGmt = Temporal::getGMTDateTime($nextBillingDate);
        } else {
            $subscriptionResumeDTO = SubscriptionResumeDTO::fromArray([
                'stripeSubscriptionId' => $subscription->transactionId,
                'billingCycleAnchor' => BillingCycleAnchor::UNCHANGED(),
                'prorationBehavior' => ProrationBehavior::NONE(),
            ]);

            $subscriptionDTO = $this->baseSubscriptionService->resumeSubscription($subscriptionResumeDTO);

            if (! $subscriptionDTO->isActive()) {
                throw new Exception('Subscription could not be resumed');
            }
        }

        $subscription->resume();

        return true;
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException|Exception
     */
    public function pause(Subscription $subscription, DateTime $resumesAt): ?bool
    {
        $subscriptionDTO = $this->baseSubscriptionService
            ->pauseSubscription($subscription->transactionId, $resumesAt);
        $paused = $subscriptionDTO->isPaused();

        if ($paused) {
            $paused = $subscription->pauseAtPeriodEnd($resumesAt);
        }

        return $paused;
    }
}
