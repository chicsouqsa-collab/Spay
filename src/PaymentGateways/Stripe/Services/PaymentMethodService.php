<?php

/**
 * Payment Method Service.
 *
 * This class is used to handle the Stripe payment methods related api request.
 *
 * @package StellarPay/PaymentGateways/Stripe/Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use StellarPay\Vendors\Illuminate\Support\LazyCollection;

/**
 * Class PaymentMethodService
 *
 * @since 1.0.0
 */
class PaymentMethodService extends StripeApiService
{
    /**
     * @throws StripeAPIException
     */
    public function getPaymentMethod(string $paymentMethodId): PaymentMethodDTO
    {
        return PaymentMethodDTO::fromStripeResponse(
            $this->httpClient->getPaymentMethod($paymentMethodId)
        );
    }

    /**
     * @since 1.0.0
     * @return LazyCollection<PaymentMethodDTO>
     * @throws StripeAPIException
     */
    public function getAllPaymentMethods(string $stripeCustomerId): LazyCollection
    {
        $paymentMethods = $this->httpClient->getAllPaymentMethods($stripeCustomerId);

        return new LazyCollection(function () use ($paymentMethods) {
            foreach ($paymentMethods as $paymentMethod) {
                yield PaymentMethodDTO::fromStripeResponse($paymentMethod);
            }
        });
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function attachPaymentMethodToCustomer(string $paymentMethodId, string $stripeCustomerId): PaymentMethodDTO
    {
        return PaymentMethodDTO::fromStripeResponse(
            $this->httpClient->attachPaymentMethodToCustomer($paymentMethodId, $stripeCustomerId)
        );
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function detachPaymentMethod(string $paymentMethodId): PaymentMethodDTO
    {
        return PaymentMethodDTO::fromStripeResponse(
            $this->httpClient->detachPaymentMethod($paymentMethodId)
        );
    }
}
