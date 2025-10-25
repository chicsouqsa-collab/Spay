<?php

/**
 * HandlesPaymentIntent Trait.
 *
 * This trait is responsible for handling the Stripe payment intent related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\PaymentIntentDTO as PaymentIntentDto;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\PaymentIntent;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesPaymentIntent
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesPaymentIntent
{
    /**
     * This method creates a payment intent given data.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createPaymentIntent(PaymentIntentDto $paymentIntent): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->create($paymentIntent->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * This method retrieves a payment intent given its id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getPaymentIntent(string $paymentIntentId, array $options = []): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->retrieve($paymentIntentId, $options);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * This method updates a payment intent given its id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updatePaymentIntent(string $paymentIntentId, array $paymentIntentData): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->update($paymentIntentId, $paymentIntentData);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
