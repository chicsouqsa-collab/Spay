<?php

/**
 * This class is responsible to provide access to invoice data present in the Stripe event.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;

/**
 * @since 1.3.0 Remove function "getInvoicePaymentIntentId", Use "getPaymentIntentId"
 * @since 1.0.0
 */
class InvoiceEventDTO extends EventDTO
{
    /**
     * @since 1.0.0
     */
    public static function fromEvent(EventDTO $event): self
    {
        return new self($event->id, $event->type, $event->data);
    }

    /**
     * @since 1.0.0
     */
    public function getSubscriptionId(): ?string
    {
        if (array_key_exists('subscription', $this->getObjectArray())) {
            return $this->data->object->subscription;  // @phpstan-ignore-line
        }

        return null;
    }

    /**
     * @since 1.0.0
     */
    public function getAmountPaid(): Money
    {
        return Money::fromMinorAmount(
            $this->data->object->amount_paid, // @phpstan-ignore-line
            $this->data->object->currency // @phpstan-ignore-line
        );
    }
}
