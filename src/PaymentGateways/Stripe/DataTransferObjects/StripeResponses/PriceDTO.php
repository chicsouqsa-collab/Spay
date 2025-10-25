<?php

/**
 * This class is used to manage the price data for Stripe.
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Price;

/**
 * Class Price
 *
 * @since 1.0.0
 */
class PriceDTO
{
    /**
     * @since 1.0.0
     */
    protected Price $stripeResponse;

    /**
     * @since 1.0.0
     */
    public static function fromStripeResponse(Price $price): self
    {
        $self = new self();

        $self->stripeResponse = $price;

        return $self;
    }

    /**
     * This method gets the id of the price.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->stripeResponse->id;
    }
}
