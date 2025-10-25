<?php

/**
 * This class use to access data from a stripe response object.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Vendors\Stripe\SubscriptionSchedule as StripeSubscriptionSchedule;

/**
 * @since 1.0.0
 */
class SubscriptionScheduleDTO
{
    /**
     * @since 1.0.0
     */
    protected StripeSubscriptionSchedule $stripeResponse;

    /**
     * This function is used to create a subscription object from a Stripe response
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeSubscriptionSchedule $response): self
    {
        $self = new self();

        $self->stripeResponse = $response;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->stripeResponse->id;
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
     * @since 1.3.0
     */
    public function willBeCanceled(): bool
    {
        return $this->isCanceled() && ($this->stripeResponse->metadata['cancelAtPeriodEnd'] ?? false);
    }
}
