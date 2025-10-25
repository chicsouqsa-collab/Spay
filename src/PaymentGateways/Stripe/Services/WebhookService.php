<?php

/**
 * This class is responsible for executing the webhook api request for the Stripe payment gateway.
 *
 * @package StellarPay\PaymentGateways\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\Stripe\Contracts\PaymentGatewayInterface;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\WebhookDTO as StripeWebhookDTO;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\PaymentGateways\Stripe\ValueObjects\Webhook as WebhookValueObject;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookRegisterer;
use StellarPay\Vendors\Illuminate\Support\LazyCollection;

/**
 * Class WebhookService
 *
 * @since 1.0.0
 */
class WebhookService extends StripeApiService
{
    /**
     * @since 1.0.0
     */
    private WebhookRegisterer $webhookRegisterer;

    /**
     * @since 1.0.0
     */
    private Webhook $webhook;

    /**
     * @since 1.0.0
     */
    public function __construct(
        PaymentGatewayInterface $httpClient,
        WebhookRegisterer $webhookRegisterer,
        Webhook $webhook
    ) {
        parent::__construct($httpClient);

        $this->webhookRegisterer = $webhookRegisterer;
        $this->webhook = $webhook;
    }

    /**
     * @since 1.0.0
     */
    public function createWebhook(string $webhookUrl): StripeWebhookDTO
    {
        $events = $this->webhookRegisterer->getEventIds();

        return StripeWebhookDTO::fromStripeResponse(
            $this->httpClient->createWebhook([
                'url' => $webhookUrl,
                'description' => esc_html__("This Stripe webhook is auto-configured and maintained by the StellarPay plugin. Any alterations to the webhook are strongly discouraged, as they may affect the plugin operations.", 'stellarpay'),
                'enabled_events' => $events,
                'api_version' => Client::STRIPE_API_VERSION
            ])
        );
    }

    /**
     * @since 1.0.0
     */
    public function updateWebhook(WebhookValueObject $webhook, PaymentGatewayMode $mode): StripeWebhookDTO
    {
        $url = $this->webhook->getEndpointByMode($mode);
        $events = $this->webhookRegisterer->getEventIds();

        return StripeWebhookDTO::fromStripeResponse(
            $this->httpClient->updateWebhook(
                $webhook->getId(),
                [
                'url' => $url,
                'enabled_events' => $events
                ]
            )
        );
    }

    /**
     * @since 1.3.0
     */
    public function updateWebhookEvents(WebhookValueObject $webhook, PaymentGatewayMode $mode): StripeWebhookDTO
    {
        $events = $this->webhookRegisterer->getEventIds();

        return StripeWebhookDTO::fromStripeResponse(
            $this->httpClient->updateWebhook(
                $webhook->getId(),
                ['enabled_events' => $events]
            )
        );
    }

    /**
     * @since 1.0.0
     */
    public function deleteWebhook(string $webhookId): bool
    {
        return $this->httpClient->deleteWebhook($webhookId);
    }

    /**
     * @since 1.0.0
     */
    public function getAllWebhooks(): LazyCollection
    {
        $webhooks = $this->httpClient->getAllWebhooks();

        return new LazyCollection(function () use ($webhooks) {
            foreach ($webhooks as $webhook) {
                yield StripeWebhookDTO::fromStripeResponse($webhook);
            }
        });
    }
}
