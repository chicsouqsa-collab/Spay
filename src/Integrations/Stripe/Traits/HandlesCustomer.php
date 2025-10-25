<?php

/**
 * HandlesCustomer Trait.
 *
 * This trait is responsible for handling the Stripe customer related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\CustomerDTO;
use StellarPay\Vendors\Stripe\Customer;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesCustomer
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesCustomer
{
    /**
     * This method retrieves a customer given its id.
     *
     * @throws StripeAPIException
     */
    public function getCustomer(string $customerId): Customer
    {
        try {
            return $this->client->customers->retrieve($customerId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * This method creates customer.
     *
     * @throws StripeAPIException
     */
    public function createCustomer(CustomerDTO $customerDTO): Customer
    {
        try {
            return $this->client->customers->create($customerDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * This method updates the customer.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updateCustomer(string $customerId, array $data): Customer
    {
        try {
            return $this->client->customers->update($customerId, $data);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
