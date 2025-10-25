<?php

/**
 * This class is responsible for handling the webhook setting data.
 *
 * @package StellarPay\PaymentGateways\Stripe\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\ValueObjects;

use InvalidArgumentException;

/**
 * Class Webhook
 *
 * @since 1.0.0
 */
class Webhook
{
    /**
     * @since 1.0.0
     */
    private array $webhookData;

    /**
     * @since 1.0.0
     */
    final public static function fromArray(array $webhookData): self
    {
        $self = new self();

        // Validate webhook data.
        // Webhook data must have and id.
        if (!isset($webhookData['id'])) {
            throw new InvalidArgumentException('Webhook data must have an id.');
        }

        $self->webhookData = $webhookData;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->webhookData['id'];
    }

    /**
     * @since 1.0.0
     */
    public function getStatus(): string
    {
        return $this->webhookData['status'];
    }


    /**
     * @since 1.0.0
     */
    public function getCreated(): int
    {
        return $this->webhookData['created'];
    }

    /**
     * @since 1.0.0
     */
    public function getSecret(): string
    {
        return $this->webhookData['secret'];
    }

    /**
     * @since 1.0.0
     */
    public function getUrl(): string
    {
        return $this->webhookData['url'];
    }

    /**
     * @since 1.0.0
     */
    public function getEvents(): array
    {
        return $this->webhookData['events'];
    }

    /**
     * @since 1.0.0
     */
    public function getApiVersion(): string
    {
        return $this->webhookData['api_version'];
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return 'enabled' === $this->getStatus();
    }

    /**
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return $this->webhookData;
    }
}
