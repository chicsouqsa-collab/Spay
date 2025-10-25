<?php

/**
 * This class is responsible to providing the definition for a dummy webhook event.
 *
 * @package StellarPay\Webhook\Factories
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\Factories;

use StellarPay\Core\Contracts\ModelFactory;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\ValueObjects\WebhookEventType;

/**
 * @since 1.1.0
 */
class WebhookEventFactory extends ModelFactory
{
    /**
     * @since 1.3.0 Add missing data.
     * @since 1.1.0
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween();
        $createdAtGmt = Temporal::getGMTDateTime($createdAt);

        return [
            'eventType' => $this->faker->randomElement(WebhookEventType::values()),
            'eventId' => uniqid('evt_'),
            'paymentGatewayMode' => $this->faker->randomElement(PaymentGatewayMode::values()),
            'sourceId' => $this->faker->unique()->numberBetween(1, 1000),
            'sourceType' => $this->faker->randomElement(WebhookEventSource::values()),
            'requestStatus' => $this->faker->randomElement(WebhookEventRequestStatus::values()),
            'createdAt' => $createdAt,
            'createdAtGmt' => $createdAtGmt,
        ];
    }
}
