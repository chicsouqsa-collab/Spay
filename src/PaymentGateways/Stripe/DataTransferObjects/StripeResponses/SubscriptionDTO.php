<?php

/**
 * Subscription model
 *
 * This model is used to represent a subscription object from Stripe
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Vendors\Stripe\Subscription as StripeSubscription;

/**
 * Class Subscription
 *
 * @since 1.0.0
 */
class SubscriptionDTO
{
    /**
     * @since 1.0.0
     */
    protected StripeSubscription $stripeResponse;

    /**
     * This function is used to create a subscription object from a Stripe response
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeSubscription $response): self
    {
        $self = new self();

        $self->stripeResponse = $response;

        return $self;
    }

    /**
     * Get the subscription ID
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->stripeResponse->id;
    }

    /**
     * Get the Stripe response.
     *
     * @since 1.0.0
     */
    public function getStripeResponseAsArray(): array
    {
        return $this->stripeResponse->toArray();
    }

    /**
     * @since 1.0.0
     */
    public function isCanceled(): bool
    {
        return SubscriptionStatus::isValid($this->stripeResponse->status)
               && SubscriptionStatus::from($this->stripeResponse->status)->isCanceled();
    }

    /**
     * @since 1.9.0
     */
    public function isPaused(): bool
    {
        return ! empty($this->stripeResponse->pause_collection);
    }

    /**
     * @since 1.9.0
     */
    public function isActive(): bool
    {
        return SubscriptionStatus::isValid($this->stripeResponse->status)
               && SubscriptionStatus::from($this->stripeResponse->status)->isActive();
    }

    /**
     * @since 1.3.0
     */
    public function willBeCanceled(): bool
    {
        return SubscriptionStatus::isValid($this->stripeResponse->status)
               && SubscriptionStatus::from($this->stripeResponse->status)->isActive()
               && $this->stripeResponse->cancel_at_period_end;
    }
}
