<?php

/**
 * This class is used to handle the subscription schedule related the Stripe rest api requests.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionScheduleDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\SubscriptionScheduleDTO as StripeResponseScheduledSubscriptionDTO;

/**
 * @since 1.4.0 Adds `get`
 * @since 1.0.0
 */
class SubscriptionScheduleService extends StripeApiService
{
    /**
     * @since 1.4.0
     */
    public function get(string $subscriptionScheduleId): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->getSubscriptionSchedule($subscriptionScheduleId)
        );
    }

    /**
     * @since 1.0.0
     */
    public function create(SubscriptionScheduleDTO $dto): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->createSubscriptionSchedule($dto)
        );
    }

    /**
     * @since 1.0.0
     */
    public function cancel(string $subscriptionScheduleId): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->cancelSubscriptionSchedule($subscriptionScheduleId)
        );
    }

    /**
     * @since 1.3.0
     */
    public function cancelAtPeriodEnd(string $subscriptionScheduleId): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->cancelAtPeriodEndSubscriptionSchedule($subscriptionScheduleId)
        );
    }

    /**
     * Update the subscription scheduled payment method.
     *
     * @since 1.1.0
     */
    public function updatePaymentMethod(string $subscriptionScheduleId, string $paymentMethodId): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->updateSubscriptionSchedulePaymentMethod($subscriptionScheduleId, $paymentMethodId)
        );
    }

    /**
     * @since 1.9.0
     */
    public function update(string $subscriptionScheduleId, SubscriptionScheduleDTO $dto): StripeResponseScheduledSubscriptionDTO
    {
        return StripeResponseScheduledSubscriptionDTO::fromStripeResponse(
            $this->httpClient->updateSubscriptionSchedule($subscriptionScheduleId, $dto)
        );
    }
}
