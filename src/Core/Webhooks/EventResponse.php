<?php

/**
 * This class is used as a response for webhook event.
 *
 * @package StellarPay\Core\Webhooks
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Webhooks;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\Webhook\Models\WebhookEvent;

/**
 * @since 1.1.0
 */
class EventResponse
{
    /**
     * @since 1.1.0
     */
    private WebhookEvent $webhookEvent;

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function setWebhookEvent(EventDTO $event): self
    {
        $this->webhookEvent = WebhookEvent::findByEventId($event->getId());

        return $this;
    }

    /**
     * @since 1.1.0
     */
    public function setWebhookEventSourceId(int $sourceId): self
    {
        $this->webhookEvent->sourceId = $sourceId;

        return $this;
    }

    /**
     * @since 1.1.0
     */
    public function setWebhookEventSourceType(WebhookEventSource $sourceType): self
    {
        $this->webhookEvent->sourceType = $sourceType;

        return $this;
    }

    /**
     * @since 1.1.0
     */
    public function setWebhookEventRequestStatus(WebhookEventRequestStatus $webhookEventRequestStatus): self
    {
        $this->webhookEvent->requestStatus = $webhookEventRequestStatus;

        return $this;
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function ensureResponse(): self
    {
        $this->webhookEvent->save();

        return $this;
    }
}
