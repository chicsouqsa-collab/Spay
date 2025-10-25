<?php

/**
 * Handles Payment Method.
 *
 * This trait is used to handle the Stripe payment methods related api request.
 *
 * @package StellarPay/Integrations/Stripe/Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Vendors\Stripe\Collection;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\PaymentMethod;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesPaymentMethod
 *
 * @since 1.0.0
 * @property-read StripeClient $client
 */
trait HandlesPaymentMethod
{
    /**
     * This method retrieves a payment method given its id.
     *
     * @since 1.0.0
     * @throws ApiErrorException
     */
    public function getPaymentMethod(string $paymentMethodId): PaymentMethod
    {
        return $this->client->paymentMethods->retrieve(
            $paymentMethodId
        );
    }

    /**
     * This method retrieves all payment methods.
     *
     * @since 1.0.0
     * @throws ApiErrorException
     * @return Collection<PaymentMethod>
     */
    public function getAllPaymentMethods(string $stripeCustomerId): Collection
    {
        // Read more: https://docs.stripe.com/api/payment_methods/customer_list
        $maxLimit = 100;

        // @phpstan-ignore-next-line the Stripe hardcoded return type in phpdoc which strauss does not override.
        return $this->client->customers->allPaymentMethods($stripeCustomerId, ['limit' => $maxLimit]);
    }

    /**
     * This method attaches a payment method to a customer.
     *
     * @since 1.0.0
     * @throws ApiErrorException
     */
    public function attachPaymentMethodToCustomer(string $paymentMethodId, string $customerId): PaymentMethod
    {
        return $this->client->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $customerId]
        );
    }

    /**
     * This method detaches a payment method.
     *
     * @since 1.0.0
     * @throws ApiErrorException
     */
    public function detachPaymentMethod(string $paymentMethodId): PaymentMethod
    {
        return $this->client->paymentMethods->detach(
            $paymentMethodId
        );
    }
}
