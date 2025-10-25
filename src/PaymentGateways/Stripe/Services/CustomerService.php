<?php

/**
 * Customer Service.
 *
 * This class is responsible to provide customer related services.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\CustomerDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\CustomerDTO as StripeResponseCustomerDTO;

/**
 * Class CustomerService
 *
 * @since 1.0.0
 */
class CustomerService extends StripeApiService
{
    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getCustomer(string $customerId): StripeResponseCustomerDTO
    {
        return StripeResponseCustomerDTO::fromStripeResponse($this->httpClient->getCustomer($customerId));
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createCustomer(CustomerDTO $customerDTO): StripeResponseCustomerDTO
    {
        return StripeResponseCustomerDTO::fromStripeResponse($this->httpClient->createCustomer($customerDTO));
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updateCustomer(string $customerId, array $data): StripeResponseCustomerDTO
    {
        return StripeResponseCustomerDTO::fromStripeResponse($this->httpClient->updateCustomer($customerId, $data));
    }
}
