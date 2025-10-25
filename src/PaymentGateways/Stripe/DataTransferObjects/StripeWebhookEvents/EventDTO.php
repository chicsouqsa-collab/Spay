<?php

/**
 * Event model for Stripe event.
 *
 * This class is responsible for handling the Stripe event data and related logic.
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

use StellarPay\Core\Exceptions\Primitives\RuntimeException;
use StellarPay\Core\ValueObjects\WebhookEventType;
use StellarPay\Vendors\Stripe\Event as StripeEvent;
use StellarPay\Vendors\Stripe\StripeObject;

/**
 * Class Event
 *
 * Read more about a Stripe event object: https://stripe.com/docs/api/events/object
 *
 * @since 1.0.0
 */
class EventDTO
{
    /**
     * @since 1.0.0
     */
    protected string $id;

    /**
     * @since 1.0.0
     */
    protected string $type;

    /**
     * @since 1.0.0
     */
    protected StripeObject $data;

    /**
     * This function creates an event from the Stripe event response.
     *
     * @since 1.0.0
     */
    final public function __construct(string $id, string $type, StripeObject $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * This function creates an event from the Stripe event response.
     *
     * @since 1.0.0
     * @return static
     */
    public static function fromStripeEventResponse(StripeEvent $event)
    {
        return new static($event->id, $event->type, $event->data);
    }

    /**
     * This function returns the event id.
     *
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * This function returns the object id.
     *
     * The stripe event contains payment intent id inside object with under different key, depends upon the event type.
     *
     * @since 1.6.0 Update logic to get payment intent id from the event object.
     * @since 1.3.0 Add a new webhook event
     * @since 1.0.0
     */
    public function getPaymentIntentId(): string
    {
        switch ($this->getType()) {
            case WebhookEventType::CHARGE_UPDATED:
            case WebhookEventType::CHARGE_REFUNDED:
            case WebhookEventType::CHARGE_REFUND_UPDATED:
            case WebhookEventType::INVOICE_PAID:
            case WebhookEventType::INVOICE_PAYMENT_FAILED:
                $paymentIntentId = $this->data->object->payment_intent; // @phpstan-ignore-line
                break;

            default:
                $paymentIntentId = $this->getObjectId();
                if (! preg_match('/^pi_[a-zA-Z0-9]+$/', $paymentIntentId)) {
                    throw new RuntimeException('Unsupported Stripe event type.');
                }
        }

        return $paymentIntentId;
    }

    /**
     * This function returns the event type.
     *
     * @since 1.0.0
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * This function returns the object type.
     *
     * @since 1.0.0
     */
    public function getObjectType(): string
    {
        return $this->data->object->object; // @phpstan-ignore-line
    }

    /**
     * This function returns the object id.
     *
     * @since 1.0.0
     */
    public function getObjectId(): string
    {
        return $this->data->object->id; // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    protected function getObjectArray(): array
    {
        return $this->data->object->toArray(); // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    protected function getMetadata(): ?array
    {
        $objectData = $this->getObjectArray();

        if (! isset($objectData['metadata'])) {
            return null;
        }

        return $objectData['metadata'];
    }

    /**
     * @since 1.1.0 Visibility changed to public
     * @since 1.0.0
     */
    public function getValueFromMetadata(string $key): ?string
    {
        $metadata = $this->getMetadata();

        if (! $metadata) {
            return null;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * @since 1.9.0
     */
    public function getFrozenTime(): ?int
    {
        $objectData = $this->getObjectArray();

        if (! isset($objectData['frozen_time'])) {
            return null;
        }

        return $objectData['frozen_time'];
    }
}
