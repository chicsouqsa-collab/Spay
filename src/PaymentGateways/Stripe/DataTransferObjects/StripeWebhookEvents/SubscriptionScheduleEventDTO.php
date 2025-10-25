<?php

/**
 * This class is responsible to provide access to subscription schedule data present in the Stripe event.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

/**
 * @since 1.0.0
 */
class SubscriptionScheduleEventDTO extends EventDTO
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
     * @return string|null
     */
    public function getSubscriptionScheduleId(): ?string
    {
        $objectData = $this->getObjectArray();

        if (array_key_exists('schedule', $objectData)) {
            return $this->data->object->schedule; // @phpstan-ignore-line
        }

        return null;
    }

    /**
     * @since 1.0.0
     */
    public function getSubscriptionId(): ?int
    {
        $subscriptionId = $this->getValueFromMetadata('subscription_id');
        return $subscriptionId ? absint($subscriptionId) : null;
    }

    /**
     * @since 1.0.0
     */
    public function getOrderId(): ?int
    {
        $orderId = $this->getValueFromMetadata('order_id');
        return $orderId ? absint($orderId) : null;
    }

    /**
     * @since 1.0.0
     */
    public function getOrderItemId(): ?int
    {
        $orderItemId = $this->getValueFromMetadata('order_item_id');
        return $orderItemId ? absint($orderItemId) : null;
    }
}
