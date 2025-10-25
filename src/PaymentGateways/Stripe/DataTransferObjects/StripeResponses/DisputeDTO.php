<?php

/**
 *
 * This class used to access the Stripe dispute details.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Dispute;

/**
 * @since 1.0.0
 */
class DisputeDTO
{
    /**
     * @since 1.0.0
     */
    public const STATUS_NEEDS_RESPONSE = Dispute::STATUS_NEEDS_RESPONSE;

    /**
     * @since 1.0.0
     */
    public const STATUS_WARNING_NEEDS_RESPONSE = Dispute::STATUS_WARNING_NEEDS_RESPONSE;

    /**
     * @since 1.0.0
     */
    protected Dispute $stripeDispute;

    /**
     * This method creates a new Dispute instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(Dispute $dispute): self
    {
        $self = new self();

        $self->stripeDispute = $dispute;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getStatus(): string
    {
        return $this->stripeDispute->status;
    }
}
