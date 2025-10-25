<?php

/**
 * CardPaymentMethodUtilities.
 *
 * This trait is responsible for managing the card payment method utilities.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\Traits;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use StellarPay\Vendors\Stripe\Card;
use StellarPay\Vendors\Stripe\PaymentMethod;

/**
 * CardPaymentMethodUtilities.
 *
 * Developer advice to check a payment method type using the "isCard" method.
 * It should be "card" to use card payment method utilities.
 *
 * @since 1.0.0
 *
 * @property ?Card $cardDetails Returns the card details.
 * @method string getType() Returns payment method type.
 */
trait CardPaymentMethodUtilities
{
    /**
     * Stripe payment method card details.
     *
     * @since 1.9.1 Use isset to prevent PHP notices
     * @since 1.0.0
     */
    protected function setCardDetails(PaymentMethod $paymentMethod): void
    {
        // Set card details if the payment method is a card.
        // This property is used by CardPaymentMethodUtilities trait.
        $this->cardDetails = isset($paymentMethod->card)
            ? Card::constructFrom($paymentMethod->card->toArray())
            : null;
    }

    /**
     * Stripe payment method card details.
     *
     * @since 1.0.0
     */
    protected ?Card $cardDetails;

    /**
     * This method retrieves the expiry month of the card.
     *
     * @since 1.0.0
     */
    public function getCardExpMonth(): int
    {
        return $this->getCardDetails()->exp_month;
    }

    /**
     * This method retrieves the expiry year of the card.
     *
     * @since 1.0.0
     */
    public function getCardExpYear(): int
    {
        return $this->getCardDetails()->exp_year;
    }

    /**
     * This method retrieves the last 4 digits of the card.
     *
     * @since 1.0.0
     */
    public function getCardLast4(): string
    {
        return $this->getCardDetails()->last4;
    }

    /**
     * This method retrieves the card brand.
     *
     * @since 1.0.0
     */
    public function getCardBrand(): string
    {
        return $this->getCardDetails()->brand;
    }

    /**
     * This method retrieves the card funding.
     *
     * @since 1.0.0
     */
    public function isCard(): bool
    {
        return $this->getType() === 'card';
    }

    /**
     * This method checks if the payment method has an equal expiry date.
     *
     * @since 1.0.0
     */
    public function hasEqualExpiryDate(PaymentMethodDTO $paymentMethod): bool
    {
        return $this->getCardExpMonth() === $paymentMethod->getCardExpMonth() &&
               $this->getCardExpYear() === $paymentMethod->getCardExpYear();
    }

    /**
     * This method retrieves a payment method given its id.
     *
     * @since 1.0.0
     */
    private function getCardDetails(): ?Card
    {
        return $this->cardDetails;
    }
}
