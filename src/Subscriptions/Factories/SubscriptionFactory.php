<?php

/**
 * This class is responsible to create fake subscription in database table.
 *
 * @package StellarPay\Subscriptions\Factories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Factories;

use StellarPay\Core\Contracts\ModelFactory;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;
use StellarPay\Core\ValueObjects\SubscriptionSource;
use StellarPay\Core\ValueObjects\SubscriptionStatus;

/**
 * @since 1.0.0
 */
class SubscriptionFactory extends ModelFactory
{
    /**
     * @since 1.0.0
     *
     * @todo: fill necessary data as per subscription status.
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTime('now');
        $createdAtGmt = Temporal::getGMTDateTime($createdAt);
        $startedAt = $this->faker->dateTime('+ 5 minutes');
        $startedAtGmt = Temporal::getGMTDateTime($startedAt);

        return [
            'customerId' => $this->faker->unique()->numberBetween(1, 1000),
            'firstOrderId' => $this->faker->unique()->numberBetween(1, 1000),
            'firstOrderItemId' => $this->faker->unique()->numberBetween(1, 50),
            'period' => $this->faker->randomElement(SubscriptionPeriod::values()),
            'frequency' => $this->faker->unique()->numberBetween(1, 100),
            'status' => $this->faker->randomElement(SubscriptionStatus::values()),
            'transactionId' => $this->faker->md5(),
            'billingTotal' => $this->faker->optional()->numberBetween(0, 100),
            'billedCount' => $this->faker->optional()->numberBetween(0, 50),
            'paymentGatewayMode' => $this->faker->randomElement([PaymentGatewayMode::LIVE(), PaymentGatewayMode::TEST()]),
            'createdAt' => $createdAt,
            'createdAtGmt' => $createdAtGmt,
            'startedAt' => $startedAt,
            'startedAtGmt' => $startedAtGmt,
            'source' => SubscriptionSource::WOOCOMMERCE(),
        ];
    }
}
