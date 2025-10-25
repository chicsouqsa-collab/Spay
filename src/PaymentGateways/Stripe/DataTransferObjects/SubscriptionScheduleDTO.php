<?php

/**
 * This class use to access data from a stripe response object.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * @since 1.0.0
 */
class SubscriptionScheduleDTO
{
    /**
     * @since 1.0.0
     */
    private ?array $dataFromStrategy = null;

    /**
     * Create a new PaymentIntent instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromDataStrategy(DataStrategy $dataStrategy): self
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
     * https://docs.stripe.com/api/subscription_schedules/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->dataFromStrategy) {
            $data = $this->dataFromStrategy;
        }

        return $data;
    }
}
