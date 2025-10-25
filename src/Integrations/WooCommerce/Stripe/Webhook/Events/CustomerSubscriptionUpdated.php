<?php

/**
 * This class is used to process the Stripe subscription event.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use Exception;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\SubscriptionEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\SubscriptionEventDTO;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Subscriptions\Repositories\SubscriptionRepository;

use function StellarPay\Core\container;

/**
 * Class CustomerSubscriptionUpdated
 *
 * @since 1.0.0
 */
class CustomerSubscriptionUpdated extends SubscriptionEventProcessor
{
    /**
     * This method processes the Stripe subscription event.
     *
     * @since 1.3.0 Use "Past due" status
     * @since 1.0.0
     * @throws Exception
     */
    protected function processSubscription(Subscription $subscription, SubscriptionEventDTO $subscriptionEvent): void
    {
        $isStripeSubscriptionPaused = $subscriptionEvent->isSubscriptionPaused();
        $isSubscriptionResumed = $subscriptionEvent->isSubscriptionResumed();
        $updated = false;

        // Update subscription status in the database based on status from Stripe.
        // Possible subscription statuses are https://docs.stripe.com/api/subscriptions/object?event_types-invoice.payment_succeeded=#subscription_object-status
        switch ($subscriptionEvent->getSubscriptionStatus()) {
            case 'active':
                if (! $subscription->expiresAt && $subscriptionEvent->isSubscriptionCancelAtPeriodEnd()) {
                    $stripeCancelTimestamp = $subscriptionEvent->getSubscriptionCanceledAt();
                    $subscription->cancelAtPeriodEnd(Temporal::getDateTimeFromUtcTimestamp($stripeCancelTimestamp));
                }

                if ($subscription->expiresAt && ! $subscriptionEvent->isSubscriptionCancelAtPeriodEnd()) {
                    $subscription->removeCancelAtPeriodEnd();
                }

                break;

            case 'incomplete_expired':
                $updated = $subscription->cancel();
                break;

            case 'unpaid':
            case 'past_due':
                $updated = $subscription->updateStatus(SubscriptionStatus::PAST_DUE());
                break;
        }

        // Do not mark the subscription as paused if the subscription is to be paused at the period end.
        if (
            $isStripeSubscriptionPaused
            && ! (
                $subscription->willPauseAtPeriodEnd()
                || $subscription->status->isPaused()
            )
        ) {
            $resumeAt = $subscriptionEvent->getResumeDate();

            if ($resumeAt) {
                $subscription->resumedAt = $resumeAt;
                $subscription->resumedAtGmt = Temporal::getGMTDateTime($resumeAt);
            }

            container(SubscriptionRepository::class)
                ->pause($subscription);
        }

        if ($isSubscriptionResumed) {
            $updated = $subscription->resume();
        }

        // Add order note if subscription status updated.
        if ($updated) {
            $this->addStatusChangeOrderNote($subscription);
        }
    }
}
