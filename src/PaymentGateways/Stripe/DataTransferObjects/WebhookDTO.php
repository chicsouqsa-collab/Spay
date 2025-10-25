<?php

/**
 * This class is responsible for managing webhook data stored in the database.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;

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
    private array $webhookData;

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    public function __construct(array $webhookData)
    {
        $this->validateDataFormat($webhookData);
        $this->webhookData = $webhookData;
    }

    /**
     * This function validates the webhook data format.
     *
     * Webhook data should have the set of the following keys separated by mode (live or test)
     * - {mode}_webhook_id
     * - {mode}_webhook_status
     * - {mode}_webhook_created
     * - {mode}_webhook_secret
     * - {mode}_webhook_url
     * - {mode}_webhook_events
     * - {mode}_webhook_api_version
     *
     * Webhook data either have data for live or test mode or both.
     *
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    private function validateDataFormat(array $webhookData): void
    {
        $keysInData = array_keys($webhookData);
        $requiredKeys = ['id', 'status', 'created', 'secret', 'url', 'events', 'api_version',];

        if ($missingKeys = array_diff($requiredKeys, $keysInData)) {
            throw new InvalidPropertyException(
                sprintf(
                    'Invalid webhook data format. Missing keys: %s',
                    implode(', ', array_map('esc_attr', $missingKeys))
                )
            );
        }
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    public static function fromArray(array $webhookData): self
    {
        return new self($webhookData);
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
    public function getEvents(): array
    {
        return $this->webhookData['events'];
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
    public function getSecret(): string
    {
        return $this->webhookData['secret'];
    }

    /**
     * @since 1.0.0
     */
    public function getWebhookUrl(): string
    {
        return $this->webhookData['url'];
    }

    /**
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return $this->webhookData;
    }
}
