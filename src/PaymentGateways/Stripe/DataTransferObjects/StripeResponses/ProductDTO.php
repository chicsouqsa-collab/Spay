<?php

/**
 * This class is used to handle the product related the Stripe rest api requests.
 *
 * @package StellarPay/PaymentGateways/Stripe/Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Product as StripeProduct;

/**
 * Class Product
 *
 * @since 1.0.0
 */
class ProductDTO
{
    /**
     * @since 1.0.0
     */
    protected StripeProduct $stripeResponse;

    /**
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeProduct $response): self
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
    public function getName(): string
    {
        return $this->stripeResponse->name;
    }

    /**
     * @since 1.0.0
     */
    public function getDescription(): ?string
    {
        return $this->stripeResponse->description;
    }

    /**
     * @since 1.0.0
     */
    public function getUrl(): ?string
    {
        return $this->stripeResponse->url;
    }
}
