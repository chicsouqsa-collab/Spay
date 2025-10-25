<?php

/**
 * Subscription Service
 *
 * This class is used to create a subscription on Stripe
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\SubscriptionDTO as StripeResponseSubscriptionDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\SubscriptionResumeDTO;
use DateTime;

/**
 * Class SubscriptionService
 *
 * @since 1.0.0
 */
class SubscriptionService extends StripeApiService
{
    /**
     * @since 1.0.0
     */
    public function getSubscription(string $subscriptionId): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->getSubscription($subscriptionId)
        );
    }

    /**
     * Create a new subscription.
     *
     * @since 1.0.0
     */
    public function createSubscription(SubscriptionDTO $subscriptionDTO): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->createSubscription($subscriptionDTO)
        );
    }

    /**
     * Update a subscription.
     *
     * @since 1.0.0
     */
    public function updateSubscription(string $stripeSubscriptionId, SubscriptionDTO $subscriptionDTO): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->updateSubscription($stripeSubscriptionId, $subscriptionDTO)
        );
    }

    /**
     * Cancel a subscription.
     *
     * @since 1.0.0
     */
    public function cancelSubscription(string $stripeSubscriptionId): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->cancelSubscription($stripeSubscriptionId)
        );
    }

    /**
     * @since 1.3.0
     */
    public function cancelAtPeriodEnd(string $stripeSubscriptionId): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->cancelAtPeriodEndSubscription($stripeSubscriptionId)
        );
    }

    /**
     * Update the subscription payment method.
     *
     * @since 1.1.0
     */
    public function updateSubscriptionPaymentMethod(string $stripeSubscriptionId, string $paymentMethodId): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->updateSubscriptionPaymentMethod($stripeSubscriptionId, $paymentMethodId)
        );
    }

    /**
     * Pause a subscription.
     *
     * @since 1.9.0
     */
    public function pauseSubscription(string $stripeSubscriptionId, DateTime $resumesAt): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->pauseSubscription($stripeSubscriptionId, $resumesAt)
        );
    }

    /**
     * Resume a subscription.
     *
     * @since 1.9.0
     */
    public function resumeSubscription(SubscriptionResumeDTO $subscriptionResumeDTO): StripeResponseSubscriptionDTO
    {
        return StripeResponseSubscriptionDTO::fromStripeResponse(
            $this->httpClient->resumeSubscription($subscriptionResumeDTO)
        );
    }
}
