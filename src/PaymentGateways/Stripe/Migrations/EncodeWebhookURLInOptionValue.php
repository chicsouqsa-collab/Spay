<?php

/**
 * This migration updates the webhook url in webhook data, which stores in the database.
 *
 * @package StellarPay\PaymentGateways\Stripe\Migrations
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Migrations;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;

use function StellarPay\Core\container;

/**
 * @since 1.3.0
 */
class EncodeWebhookURLInOptionValue extends Migration
{
    /**
     * @inheritdoc
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function run(): void
    {
        $accountRepository = container(AccountRepository::class);
        $webhookRepository = container(WebhookRepository::class);

        if (! $accountRepository->isLiveModeConnected()) {
            return;
        }

        $liveWebhook = $webhookRepository->getWebhook(PaymentGatewayMode::LIVE());
        $testWebhook = $webhookRepository->getWebhook(PaymentGatewayMode::TEST());

        if ($liveWebhook) {
            $liveWebhookData = $liveWebhook->toArray();
            $liveWebhookData['url'] = base64_encode($liveWebhook->getUrl());
            update_option($webhookRepository->getWebhookDataKey(PaymentGatewayMode::LIVE()), $liveWebhookData);
        }

        if ($testWebhook) {
            $testWebhookData = $testWebhook->toArray();
            $testWebhookData['url'] = base64_encode($testWebhook->getUrl());
            update_option($webhookRepository->getWebhookDataKey(PaymentGatewayMode::TEST()), $testWebhookData);
        }
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function id(): string
    {
        return 'encode-webhook-url-in-option_value';
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function title(): string
    {
        return esc_html__('Encode Webhook URL in Option Value', 'stellarpay');
    }

    /**
     * @inheritdoc
     * @since
     */
    public static function timestamp(): int
    {
        return strtotime('2025-01-20 15:56:00');
    }
}
