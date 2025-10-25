<?php

/**
 * DTO for Webhook Events.
 *
 * @package StellarPay\Webhook\DataTransferObjects
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\DataTransferObjects;

use DateTimeInterface;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\ValueObjects\WebhookEventType;

/**
 * @since 1.1.0
 */
class WebhookEventQueryData
{
    /**
     * @since 1.1.0
     */
    public int $id;

    /**
     * @since 1.1.0
     */
    public WebhookEventType $eventType;

    /**
     * @since 1.1.0
     */
    public ?DateTimeInterface $createdAt;

    /**
     * @since 1.1.0
     */
    public ?DateTimeInterface $createdAtGmt;

    /**
     * @since 1.1.0
     */
    public ?int $sourceId;

    /**
     * @since 1.1.0
     */
    public PaymentGatewayMode $paymentGatewayMode;

    /**
     * @since 1.1.0
     */
    public string $eventId;

    /**
     * @since 1.1.0
     */
    public WebhookEventSource $sourceType;

    /**
     * @since 1.1.0
     */
    public ?DateTimeInterface $responseTime;

    /**
     * @since 1.1.0
     */
    public ?DateTimeInterface $responseTimeGmt;

    /**
     * @since 1.1.0
     */
    public WebhookEventRequestStatus $requestStatus;

    /**
     * @since 1.1.0
     */
    public array $notes;

    /**
     * @since 1.1.0
     */
    public static function fromObject(object $webhookEventQueryObject): self
    {
        $self = new self();

        $self->id = absint($webhookEventQueryObject->id);
        $self->eventType = WebhookEventType::from($webhookEventQueryObject->event_type);
        $self->eventId = $webhookEventQueryObject->event_id;
        $self->createdAt = Temporal::toDateTimeFromMilliseconds($webhookEventQueryObject->created_at);
        $self->createdAtGmt = Temporal::toGMTDateTimeFromMilliseconds($webhookEventQueryObject->created_at_gmt);
        $self->sourceId = absint($webhookEventQueryObject->source_id);
        $self->paymentGatewayMode = PaymentGatewayMode::from($webhookEventQueryObject->payment_gateway_mode);
        $self->requestStatus = WebhookEventRequestStatus::from($webhookEventQueryObject->request_status);
        $self->sourceType = WebhookEventSource::from($webhookEventQueryObject->source_type);
        $self->responseTime = $webhookEventQueryObject->response_time ? Temporal::toDateTimeFromMilliseconds($webhookEventQueryObject->response_time) : null;
        $self->responseTimeGmt = $webhookEventQueryObject->response_time_gmt ? Temporal::toGMTDateTimeFromMilliseconds($webhookEventQueryObject->response_time_gmt) : null;
        $self->notes = $webhookEventQueryObject->notes ? json_decode($webhookEventQueryObject->notes, true) : [];

        return $self;
    }

    /**
     * Convert DTO to WebhookEvent model.
     *
     * @since 1.1.0
     */
    public function toModel(): WebhookEvent
    {
        $attributes = get_object_vars($this);

        return new WebhookEvent($attributes);
    }
}
