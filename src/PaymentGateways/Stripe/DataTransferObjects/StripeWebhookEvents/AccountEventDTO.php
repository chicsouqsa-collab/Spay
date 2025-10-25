<?php

/**
 * This class used to manage the Stripe account event details.
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\AccountDTO;

/**
 * Class Account
 *
 * @since 1.0.0
 */
class AccountEventDTO extends EventDTO
{
    /**
     * @since 1.0.0
     */
    public static function fromEvent(EventDTO $event): AccountEventDTO
    {
        return new self($event->id, $event->type, $event->data);
    }

    /**
     * @since 1.0.0
     */
    public function getAccount(): AccountDTO
    {
        return AccountDTO::fromStripeResponse($this->data->object); // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function isAccountSettingsUpdated(): bool
    {
        if (null === $this->data->previous_attributes) { // @phpstan-ignore-line
            return false;
        }

        $previousAttributesArray = $this->data->previous_attributes->toArray();

        return ! empty($previousAttributesArray);
    }
}
