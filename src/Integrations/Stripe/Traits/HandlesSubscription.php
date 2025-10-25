<?php

/**
 * Subscription Strategy Interface.
 *
 * This trait is responsible to handle the subscription related logic.
 *
 * @package StellarPay/PaymentGateways/Stripe/Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\SubscriptionResumeDTO;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;
use StellarPay\Vendors\Stripe\Subscription;
use DateTime;

/**
 * Trait HandlesSubscription
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesSubscription
{
    /**
     * Create a new subscription.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        try {
            return $this->client->subscriptions->retrieve($subscriptionId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Create a new subscription.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createSubscription(SubscriptionDTO $subscriptionDTO): Subscription
    {
        try {
            return $this->client->subscriptions->create($subscriptionDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Update a subscription.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updateSubscription(string $stripeSubscriptionId, SubscriptionDTO $subscriptionDTO): Subscription
    {
        try {
            return $this->client->subscriptions->update($stripeSubscriptionId, $subscriptionDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Update a subscription payment method.
     *
     * @since 1.1.0
     * @throws StripeAPIException
     */
    public function updateSubscriptionPaymentMethod(string $stripeSubscriptionId, string $paymentMethodId): Subscription
    {
        try {
            return $this->client->subscriptions->update($stripeSubscriptionId, ['default_payment_method' => $paymentMethodId]);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Resume a subscription.
     *
     * @since 1.9.0
     * @throws StripeAPIException
     */
    public function resumeSubscription(SubscriptionResumeDTO $subscriptionResumeDTO): Subscription
    {
        try {
            return $this->client->subscriptions->update($subscriptionResumeDTO->getStripeSubscriptionId(), $subscriptionResumeDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Pause a subscription.
     *
     * @since 1.9.0
     * @throws StripeAPIException
     */
    public function pauseSubscription(string $stripeSubscriptionId, DateTime $resumesAt): Subscription
    {
        try {
            return $this->client->subscriptions->update(
                $stripeSubscriptionId,
                [
                    'pause_collection' => [
                        'behavior'   => 'void',
                        'resumes_at' => $resumesAt->getTimestamp(),
                    ],
                ]
            );
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Update a subscription.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function cancelSubscription(string $stripeSubscriptionId): Subscription
    {
        try {
            return $this->client->subscriptions->cancel($stripeSubscriptionId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Cancel a subscription at the end of the current period.
     *
     * @since 1.3.0
     * @throws StripeAPIException
     */
    public function cancelAtPeriodEndSubscription(string $stripeSubscriptionId): Subscription
    {
        try {
            return $this->client->subscriptions->update($stripeSubscriptionId, ['cancel_at_period_end' => true]);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
