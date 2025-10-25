<?php

/**
 * This class is used to process the Stripe subscription event.
 * For example: customer.subscription.updated, customer.subscription.deleted, etc.
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

use DateTime;
use StellarPay\Core\Support\Facades\DateTime\Temporal;

/**
 * Class SubscriptionEvent
 *
 * @since 1.0.0
 */
class SubscriptionEventDTO extends EventDTO
{
    /**
     * This function creates an event from the event model.
     *
     * @since 1.0.0
     */
    public static function fromEvent(EventDTO $event): self
    {
        return new self($event->id, $event->type, $event->data);
    }

    /**
     * @since 1.4.0
     */
    public function getPreviousAttributes(): ?object
    {
        if (empty($this->data->previous_attributes)) {
            return null;
        }

        return $this->data->previous_attributes;
    }

    /**
     * This function creates an event from the Stripe event response.
     *
     * @since 1.0.0
     */
    public function getSubscriptionId(): string
    {
        return $this->getObjectId();
    }

    /**
     * This function creates an event from the Stripe event response.
     *
     * @since 1.0.0
     */
    public function getSubscriptionStatus(): string
    {
        return $this->data->object->status; // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function isSubscriptionPaused(): bool
    {
        $objectData = $this->getObjectArray();

        return array_key_exists('pause_collection', $objectData) && $objectData['pause_collection'];
    }

    /**
     * @since 1.4.0
     */
    public function getResumeDate(): ?DateTime
    {
        $objectData = $this->getObjectArray();

        return $objectData['pause_collection']['resumes_at']
            ? Temporal::getDateTimeFromUtcTimestamp($objectData['pause_collection']['resumes_at'])
            : null;
    }

    /**
     * @since 1.4.0
     */
    public function isSubscriptionResumed(): bool
    {
        $previousAttributes = $this->getPreviousAttributes();

        if (empty($previousAttributes)) {
            return false;
        }

        return ! empty($previousAttributes->pause_collection);
    }

    /**
     * @since 1.0.0
     */
    public function isSubscriptionCanceled(): bool
    {
        $objectData = $this->getObjectArray();

        return array_key_exists('canceled_at', $objectData) && $objectData['canceled_at'];
    }

    /**
     * @since 1.3.0
     */
    public function isSubscriptionCancelAtPeriodEnd(): bool
    {
        $objectData = $this->getObjectArray();

        return $objectData['cancel_at_period_end'] ?? false;
    }

    /**
     * @since 1.3.0
     */
    public function getSubscriptionCanceledAt(): ?int
    {
        $objectData = $this->getObjectArray();

        return $objectData['canceled_at'] ?? null;
    }
}
