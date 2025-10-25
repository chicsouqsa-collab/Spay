<?php

/**
 * PaymentMethod Model.
 *
 * This class is responsible manage Stripe payment method.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\Traits\CardPaymentMethodUtilities;
use StellarPay\Vendors\Stripe\PaymentMethod as StripePaymentMethod;

/**
 * Class PaymentMethod
 *
 * @since 1.0.0
 */
class PaymentMethodDTO
{
    use CardPaymentMethodUtilities;

    /**
     * Stripe payment method type.
     *
     * @since 1.0.0
     */
    private string $type;

    /**
     * Stripe payment method id.
     *
     * @since 1.0.0
     */
    private string $id;

    /**
     * Stripe payment method.
     *
     * @since 1.0.0
     */
    private StripePaymentMethod $paymentMethod;

    /**
     * This method creates a new Account instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripePaymentMethod $paymentMethod): self
    {
        $self = new self();

        $self->id = $paymentMethod->id;
        $self->type = $paymentMethod->type;
        $self->paymentMethod = $paymentMethod;

        // in support of CardPaymentMethodUtilities trait
        $self->setCardDetails($paymentMethod);

        return $self;
    }

    /**
     * This method retrieves a payment method given its id.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * This method retrieves a payment method given its id.
     *
     * @since 1.0.0
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * This method retrieves a payment method given its id.
     *
     * Stripe generates fingerprint a few payment method types.
     * We can use this fingerprint to detect duplicate cards.
     * A few payment method types that Stripe generates fingerprint are:
     * - card
     * - us_bank_account
     * - sepa_debit
     *
     * Read more: https://support.stripe.com/questions/how-can-i-detect-duplicate-cards-or-bank-accounts
     *
     * @since 1.0.0
     */
    public function getFingerprint(): ?string
    {
        $paymentMethodDetailsObject = $this->paymentMethod->{$this->type}->toArray();

        return $paymentMethodDetailsObject['fingerprint'] ?? null;
    }

    /**
     * This method checks if the payment method has the same type as the given payment method.
     *
     * @since 1.0.0
     */
    public function hasSameType(PaymentMethodDTO $paymentMethod): bool
    {
        return $this->getType() === $paymentMethod->getType();
    }

    /**
     * This method checks if the payment method has the same fingerprint as the given payment method.
     *
     * @since 1.0.0
     */
    public function hasSameFingerprint(PaymentMethodDTO $paymentMethod): bool
    {
        return $this->getFingerprint() === $paymentMethod->getFingerprint();
    }

    /**
     * This method checks if the payment method has the same id as the given id.
     *
     * @since 1.0.0
     */
    public function hasId(string $id): bool
    {
        return $this->getId() === $id;
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentGatewayMode(): PaymentGatewayMode
    {
        return $this->paymentMethod->livemode ? PaymentGatewayMode::live() : PaymentGatewayMode::test();
    }
}
