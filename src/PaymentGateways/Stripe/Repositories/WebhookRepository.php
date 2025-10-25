<?php

/**
 * WebhookRepository
 *
 * This class is responsible for handling the webhook setting data.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Repositories;

use InvalidArgumentException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\WebhookDTO as StripeWebhookDTO;
use StellarPay\PaymentGateways\Stripe\ValueObjects\Webhook;

use function StellarPay\Core\dbOptionKeyGenerator;

/**
 * Class WebhookRepository
 *
 * @since 1.0.0
 */
class WebhookRepository
{
    /**
     * @since 1.0.0
     */
    public function getWebhookDataKey(PaymentGatewayMode $paymentGatewayMode): string
    {
        return dbOptionKeyGenerator('stripe_webhook_data_' . $paymentGatewayMode->getId());
    }

    /**
     * This function returns the webhook secret key.
     *
     * @since 1.0.0
     */
    public function getSecretKey(PaymentGatewayMode $mode): string
    {
        $webhookData = $this->getWebhook($mode);

        return $webhookData->getSecret();
    }

    /**
     * @since 1.0.0
     */
    public function saveWebhook(StripeWebhookDTO $webhook, PaymentGatewayMode $paymentGatewayMode): bool
    {
        $webhookData = $this->getWebhook($paymentGatewayMode);

        $webhookData = array_merge(
            $webhookData ? $webhookData->toArray() : [],
            $this->formatStripeWebhookDtoToArray($webhook)
        );

        return update_option(
            $this->getWebhookDataKey($paymentGatewayMode),
            $webhookData,
            false
        );
    }

    /**
     * @since 1.0.0
     */
    public function getWebhook(PaymentGatewayMode $paymentGatewayMode): ?Webhook
    {
        $result = get_option($this->getWebhookDataKey($paymentGatewayMode), null);

        if ($result) {
            $result['url'] = base64_decode($result['url']);

            return Webhook::fromArray($result);
        }

        return null;
    }

    /**
     * @since 1.0.0
     * @throws InvalidArgumentException
     */
    public function deleteWebhook(PaymentGatewayMode $paymentGatewayMode): bool
    {
        return delete_option($this->getWebhookDataKey($paymentGatewayMode));
    }

    /**
     * @since 1.0.0
     */
    public function formatStripeWebhookDtoToArray(StripeWebhookDTO $webhookDTO): array
    {
        $data =  [
            'id' => $webhookDTO->getId(),
            'status' => $webhookDTO->isEnabled() ? 'enabled' : 'disabled',
            'created' => $webhookDTO->getCreatedDate(),
            'url' => base64_encode($webhookDTO->getWebhookListenerURL()),
            'events' => $webhookDTO->getEvents(),
        ];

        if ($secretKey = $webhookDTO->getSecretKey()) {
            $data["secret"] = $secretKey;
        }

        if ($apiVersion = $webhookDTO->getApiVersion()) {
            $data["api_version"] = $apiVersion;
        }

        return  $data;
    }
}
