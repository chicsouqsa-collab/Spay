<?php

/**
 * PaymentIntent Model.
 *
 * This class is responsible manage Stripe payment intent.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Core\ValueObjects\Money;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Stripe\ValueObjects\PaymentIntentStatus;
use StellarPay\Vendors\Stripe\PaymentIntent as StripePaymentIntent;

/**
 * Class PaymentIntent
 *
 * @since 1.0.0
 */
class PaymentIntentDTO
{
    /**
     * @since 1.0.0
     */
    public const SETUP_FUTURE_USAGE_OFF_SESSION = StripePaymentIntent::SETUP_FUTURE_USAGE_OFF_SESSION;

    /**
     * Stripe payment intent id.
     *
     * @since 1.0.0
     */
    private string $id;

    /**
     * Stripe payment intent amount.
     *
     * @since 1.0.0
     */
    private int $amount;

    /**
     * Stripe payment intent currency.
     *
     * @since 1.0.0
     */
    private string $currency;

    /**
     * Stripe payment intent client secret.
     *
     * @since 1.0.0
     */
    private ?string $clientSecret;

    /**
     * Stripe payment intent response.
     *
     * @since 1.0.0
     */
    private StripePaymentIntent $stripeResponse;

    /**
     * Create a new PaymentIntent instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripePaymentIntent $response): self
    {
        $paymentIntent = new self();

        $paymentIntent->id = $response->id;
        $paymentIntent->amount = $response->amount;
        $paymentIntent->currency = $response->currency;
        $paymentIntent->clientSecret = $response->client_secret;

        $paymentIntent->stripeResponse = $response;

        return $paymentIntent;
    }

    /**
     * Get the payment intent id.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the payment intent amount.
     *
     * @since 1.0.0
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get the payment intent currency.
     *
     * @since 1.0.0
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get the payment intent client secret.
     *
     * Note
     * Client secret returns in payment intent api when created, after that this value is not available.
     *
     * @since 1.0.0
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Get the payment method id.
     *
     * @since 1.0.0
     */
    public function getPaymentMethod(): string
    {
        return $this->stripeResponse->payment_method;
    }

    /**
     * Get the customer id.
     *
     * @since 1.0.0
     */
    public function getCustomer(): string
    {
        return $this->stripeResponse->customer;
    }

    /**
     * Get the payment intent response as an array.
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
    public function getPaymentGatewayMode(): PaymentGatewayMode
    {
        return $this->stripeResponse->livemode ? PaymentGatewayMode::live() : PaymentGatewayMode::test();
    }

    /**
     * This function returns the total fee applied by the Stripe on payment.
     *
     * Note
     * Uses this function only when api the Stripe spi response has "balance_transaction" in response,
     * Otherwise you will get unexpected results.
     *
     * To get "balance_transaction" expand "payment_intent" api response.
     * ['expand' => ['latest_charge.balance_transaction']
     *
     * @return Money
     */
    public function getFee(): Money
    {
        $balanceTransaction = $this->stripeResponse->latest_charge->balance_transaction;

        return Money::fromMinorAmount(
            $balanceTransaction->fee,
            $balanceTransaction->currency
        );
    }

    /**
     * This function returns the net amount for order which Stripe release on payout.
     *
     * Note
     * Uses this function only when api the Stripe spi response has "balance_transaction" in response,
     * Otherwise you will get unexpected results.
     *
     * To get "balance_transaction" expand "payment_intent" api response.
     * ['expand' => ['latest_charge.balance_transaction']
     *
     * @return Money
     */
    public function getNetAmount(): Money
    {
        $balanceTransaction = $this->stripeResponse->latest_charge->balance_transaction;

        return Money::fromMinorAmount(
            $balanceTransaction->net,
            $balanceTransaction->currency
        );
    }

    /**
     * Check if the payment intent is canceled.
     *
     * @since 1.0.0
     */
    public function isCanceled(): bool
    {
        return StripePaymentIntent::STATUS_CANCELED === $this->stripeResponse->status;
    }

    /**
     * Check if the payment intent is succeeded.
     *
     * @since 1.0.0
     */
    public function isSucceeded(): bool
    {
        return StripePaymentIntent::STATUS_SUCCEEDED === $this->stripeResponse->status;
    }

    /**
     * @since 1.4.1
     */
    public function getStatus(): PaymentIntentStatus
    {
        return PaymentIntentStatus::from($this->stripeResponse->status);
    }
}
