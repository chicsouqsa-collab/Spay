<?php

/**
 * This class represents Stripe payment method domain response.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\PaymentMethodDomain;

/**
 * Class PaymentMethodDomainDTO
 *
 * @since 1.0.0
 */
class PaymentMethodDomainDTO
{
    /**
     * @since 1.0.0
     */
    private PaymentMethodDomain $stripePaymentMethodDomain;

    /**
     * @since 1.0.0
     */
    public static function fromStripeResponse(PaymentMethodDomain $paymentMethodDomain): self
    {
        $self = new self();

        $self->stripePaymentMethodDomain = $paymentMethodDomain;

        return $self;
    }

    /**
     * This function returns payment method domain id.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->stripePaymentMethodDomain->id;
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->stripePaymentMethodDomain->enabled;
    }

    /**
     * @since 1.0.0
     */
    public function getDomain(): string
    {
        return $this->stripePaymentMethodDomain->domain_name;
    }

    /**
     * @since 1.0.0
     */
    public function getApplePayStatus(): array
    {
        return $this->stripePaymentMethodDomain->apple_pay->toArray();
    }

    /**
     * @since 1.0.0
     */
    public function getGooglePayStatus(): array
    {
        return $this->stripePaymentMethodDomain->google_pay->toArray();
    }

    /**
     * @since 1.0.0
     */
    public function getPayPalStatus(): array
    {
        return $this->stripePaymentMethodDomain->paypal->toArray();
    }
}
