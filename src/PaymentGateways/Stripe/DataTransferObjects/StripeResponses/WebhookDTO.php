<?php

/**
 * This class used to manage the Stripe webhook details.
 *
 * @package StellarPay\PaymentGateways\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\WebhookEndpoint;

/**
 * Class Webhook
 *
 * @since 1.0.0
 */
class WebhookDTO
{
    /**
     * @since 1.0.0
     */
    private WebhookEndpoint $webhookEndpoint;

    /**
     * @since 1.0.0
     */
    public static function fromStripeResponse(WebhookEndpoint $webhookEndpoint): self
    {
        $self = new self();

        $self->webhookEndpoint = $webhookEndpoint;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->webhookEndpoint->id;
    }

    /**
     * @since 1.0.0
     */
    public function getSecretKey(): ?string
    {
        return $this->webhookEndpoint->secret;
    }

    /**
     * @since 1.0.0
     */
    public function getCreatedDate(): int
    {
        return $this->webhookEndpoint->created;
    }

    /**
     * @since 1.0.0
     */
    public function getWebhookListenerURL(): string
    {
        return $this->webhookEndpoint->url;
    }

    /**
     * @since 1.0.0
     */
    public function getEvents(): array
    {
        return $this->webhookEndpoint->enabled_events;
    }

    /**
     * @since 1.0.0
     */
    public function getApiVersion(): ?string
    {
        return $this->webhookEndpoint->api_version;
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return 'enabled' === $this->webhookEndpoint->status;
    }

    /**
     * @since 1.0.0
     */
    public function isLiveMode(): bool
    {
        return $this->webhookEndpoint->livemode;
    }
}
