<?php

/**
 * Stripe API Service.
 *
 * This class is responsible to provide Stripe payment intent related services.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\PaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentIntentDTO
    as StripeResponsePaymentIntentDTO;

/**
 * Class PaymentIntent
 *
 * @since 1.0.0
 */
class PaymentIntentService extends StripeApiService
{
    /**
     * This function creates Stripe payment intent.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createPaymentIntent(PaymentIntentDTO $paymentIntent): StripeResponsePaymentIntentDTO
    {
        return StripeResponsePaymentIntentDTO::fromStripeResponse(
            $this->httpClient->createPaymentIntent($paymentIntent)
        );
    }

    /**
     * This function gets Stripe payment intent.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getPaymentIntent(string $paymentIntentId, array $options = []): StripeResponsePaymentIntentDTO
    {
        return StripeResponsePaymentIntentDTO::fromStripeResponse(
            $this->httpClient->getPaymentIntent($paymentIntentId, $options)
        );
    }

    /**
     * This function updates Stripe payment intent.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updatePaymentIntent(
        string $paymentIntentId,
        array $paymentIntentData
    ): StripeResponsePaymentIntentDTO {
        return StripeResponsePaymentIntentDTO::fromStripeResponse(
            $this->httpClient->updatePaymentIntent(
                $paymentIntentId,
                $paymentIntentData
            )
        );
    }
}
