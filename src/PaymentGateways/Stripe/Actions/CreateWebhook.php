<?php

/**
 * This class is responsible for creating a new webhook and saving it to a database.
 * This creates a new webhook based on the mode provided.
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\PaymentGateways\Stripe\Services\WebhookService;

/**
 * Class SaveWebhook
 *
 * @since 1.3.0 Rename class
 * @since 1.0.0
 */
class CreateWebhook
{
    /**
     * @since 1.0.0
     */
    private WebhookService $webhookService;

    /**
     * @since 1.0.0
     */
    private WebhookRepository $webhookRepository;

    /**
     * @since 1.0.0
     */
    private Webhook $webhookRestApi;

    /**
     * @since 1.0.0
     */
    public function __construct(
        WebhookService $webhookService,
        WebhookRepository $webhookRepository,
        Webhook $webhookRestApi
    ) {
        $this->webhookService = $webhookService;
        $this->webhookRepository = $webhookRepository;
        $this->webhookRestApi = $webhookRestApi;
    }

    /**
     * @since 1.0.0
     */
    public function __invoke(PaymentGatewayMode $paymentGatewayMode): bool
    {
        $webhookUrl = $this->webhookRestApi->getEndpointByMode($paymentGatewayMode);
        $webhookData = $this->webhookService->createWebhook($webhookUrl);

        return $this->webhookRepository->saveWebhook($webhookData, $paymentGatewayMode);
    }
}
