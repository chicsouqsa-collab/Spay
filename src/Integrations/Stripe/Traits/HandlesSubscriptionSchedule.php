<?php

/**
 * This trait provides logics for subscription schedule.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @aince 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionScheduleDTO;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;
use StellarPay\Vendors\Stripe\SubscriptionSchedule;

/**
 * @since 1.9.0 adds `updateSubscriptionSchedule`
 * @since 1.4.0 Adds `getSubscriptionSchedule`
 * @since 1.0.0
 *
 * @property StripeClient $client
 */
trait HandlesSubscriptionSchedule
{
    /**
     * @since 1.4.0
     * @throws StripeAPIException
     */
    public function getSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule
    {
        try {
            return $this->client->subscriptionSchedules->retrieve($subscriptionScheduleId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createSubscriptionSchedule(SubscriptionScheduleDTO $dto): SubscriptionSchedule
    {
        try {
            return $this->client->subscriptionSchedules->create($dto->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function cancelSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule
    {
        try {
            return $this->client->subscriptionSchedules->cancel($subscriptionScheduleId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.3.0
     * @throws StripeAPIException
     */
    public function cancelAtPeriodEndSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule
    {
        try {
            $this->client->subscriptionSchedules->update($subscriptionScheduleId, ['metadata' => ['cancelAtPeriodEnd' => true]]);

            return $this->cancelSubscriptionSchedule($subscriptionScheduleId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Update a subscription scheduled payment method.
     *
     * @since 1.1.0
     * @throws StripeAPIException
     */
    public function updateSubscriptionSchedulePaymentMethod(string $subscriptionScheduleId, string $paymentMethodId): SubscriptionSchedule
    {
        try {
            return $this->client->subscriptionSchedules->update($subscriptionScheduleId, ['default_settings' => ['default_payment_method' => $paymentMethodId]]);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * Update a subscription schedule start date.
     *
     * @since 1.9.0
     * @throws StripeAPIException
     */
    public function updateSubscriptionSchedule(string $subscriptionScheduleId, SubscriptionScheduleDTO $dto): SubscriptionSchedule
    {
        try {
            return $this->client->subscriptionSchedules->update($subscriptionScheduleId, $dto->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
