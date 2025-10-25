<?php

/**
 * This class used to manage account details.
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Account as StripeAccount;

/**
 * Class Account
 *
 * @since 1.0.0
 */
class AccountDTO
{
    /**
     * @since 1.0.0
     */
    private StripeAccount $stripeAccount;

    /**
     * This method creates a new Account instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeAccount $account): AccountDTO
    {
        $self = new self();

        $self->stripeAccount = $account;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountId(): string
    {
        return $this->stripeAccount->id;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountCountry(): string
    {
        return $this->stripeAccount->country;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountCurrency(): string
    {
        return $this->stripeAccount->default_currency;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountName(): string
    {
        return $this->stripeAccount->settings->dashboard->display_name; // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function getStatementDescriptor(): string
    {
        return $this->stripeAccount->settings->payments // @phpstan-ignore-line
        ->statement_descriptor;
    }

    /**
     * @since 1.0.0
     */
    public function getLogoImageId(): ?string
    {
        return $this->stripeAccount->settings->branding->logo; // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function getIconImageId(): ?string
    {
        return $this->stripeAccount->settings->branding->icon; // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function hasController(): bool
    {
        return null !== $this->stripeAccount->controller;
    }
}
