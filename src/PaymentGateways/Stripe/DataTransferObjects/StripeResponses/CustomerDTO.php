<?php

/**
 * Customer Model.
 *
 * This class is responsible manage Stripe customer.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Customer as StripeCustomer;

/**
 * Class Customer
 *
 * @since 1.0.0
 */
class CustomerDTO
{
    /**
     * Stripe customer id.
     *
     * @var string
     *
     * @since 1.0.0
     */
    private string $id;

    /**
     * Stripe customer email.
     *
     * @var string
     *
     * @since 1.0.0
     */
    private string $email;

    /**
     * Stripe customer deleted status.
     *
     * @var bool
     *
     * @since 1.0.0
     */

    private bool $deleted;

    /**
     * Stripe customer response.
     *
     * @since 1.0.0
     */
    private StripeCustomer $stripeResponse;

    /**
     * Create a new Customer instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeCustomer $response): self
    {
        $self = new self();

        $self->id = $response->id;
        $self->deleted = $response->isDeleted();

        // Stripe response only contains id if the deleted status is true.
        // For this reason, we should not set the email if the customer is deleted.
        if (! $self->deleted) {
            $self->email = $response->email;
        }

        $self->stripeResponse = $response;

        return $self;
    }

    /**
     * Get the customer id.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the customer email.
     *
     * @since 1.0.0
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the customer deleted status.
     *
     * @since 1.0.0
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
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
    public function getCreatedDate(): int
    {
        return $this->stripeResponse->created;
    }
}
