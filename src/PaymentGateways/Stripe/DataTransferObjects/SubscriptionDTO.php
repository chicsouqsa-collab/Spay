<?php

/**
 * Subscription Data Transfer Object.
 *
 * This class is used to create a new PaymentIntent instance from a WooCommerce order.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Class SubscriptionDTO
 *
 * @since 1.0.0
 */
class SubscriptionDTO
{
    /**
     * @since 1.0.0
     */
    private ?array $dataFromStrategy = null;

    /**
     * @since 1.0.0
     */
    public ?string $status = null;

    /**
     * Create a new PaymentIntent instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromSubscriptionDataStrategy(DataStrategy $dataStrategy): self
    {
        $paymentIntent = new self();

        $paymentIntent->dataFromStrategy = $dataStrategy->generateData();

        return $paymentIntent;
    }

    /**
     * Convert the object to an array.
     *
     * This function returns data as an array which is compatible with the Stripe API.
     * You can check a subscription object in Stripe API documentation for more information.
     * https://docs.stripe.com/api/subscriptions/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->dataFromStrategy) {
            $data = $this->dataFromStrategy;
        }

        if ($this->status) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}
