<?php

/**
 * Customer Data Transfer Object.
 *
 * This class is responsible to manage the customer data transfer object.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Class CustomerDTO
 *
 * @since 1.0.0
 */
class CustomerDTO
{
    /**
     * Stripe customer email.
     *
     * @since 1.0.0
     */
    public string $email;

    /**
     * Stripe customer name.
     *
     * @since 1.0.0
     */
    public string $name;

    /**
     * @since 1.0.0
     */
    public array $dataFromStrategy;

    /**
     * Create a new CustomerDTO instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromCustomerDataStrategy(DataStrategy $dataStrategy): self
    {
        $customer = new self();

        $customer->dataFromStrategy = $dataStrategy->generateData();
        $customer->email = $customer->dataFromStrategy['email'];
        $customer->name = $customer->dataFromStrategy['name'];

        return $customer;
    }

    /**
     * Convert the object to an array.
     *
     * This function returns results which are compatible with the Stripe API.
     * You can check a customer object in Stripe API documentation for more information.
     * Link - https://docs.stripe.com/api/customers/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = $this->dataFromStrategy;
        $data['name'] = $this->name;
        $data['email'] = $this->email;

        return $data;
    }
}
