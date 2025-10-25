<?php

/**
 * This trait is responsible for handling the Stripe webhook related logic.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Vendors\Stripe\Collection;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;
use StellarPay\Vendors\Stripe\WebhookEndpoint;

/**
 * Trait HandlesWebhook
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesWebhook
{
    /**
     * This method creates a new webhook endpoint.
     *
     * @throws StripeAPIException
     */
    public function createWebhook(array $params): WebhookEndpoint
    {
        try {
            return $this->client->webhookEndpoints->create($params);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @throws StripeAPIException
     */
    public function updateWebhook(string $webhookId, array $params): WebhookEndpoint
    {
        try {
            return $this->client->webhookEndpoints->update($webhookId, $params);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @throws StripeAPIException
     */
    public function deleteWebhook(string $webhookId): bool
    {
        try {
            return $this->client->webhookEndpoints->delete($webhookId)->isDeleted();
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getAllWebhooks(): Collection
    {
        try {
            return $this->client->webhookEndpoints->all(['limit' => 100]);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
