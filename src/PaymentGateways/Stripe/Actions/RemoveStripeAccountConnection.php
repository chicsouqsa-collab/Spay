<?php

/**
 * This action uses to remove the Stripe account connection.
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Client;
use StellarPay\Integrations\Stripe\Client as StripeClient;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;
use StellarPay\PaymentGateways\Stripe\Services\WebhookService;

use function StellarPay\Core\remote_get;

/**
 * @since 1.1.0
 */
final class RemoveStripeAccountConnection
{
    /**
     * @since 1.1.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.1.0
     */
    private PaymentGatewayMode $paymentGatewayMode;

    /**
     * @since 1.1.0
     */
    private WebhookRepository $webhookRepository;

    /**
     * @since 1.1.0
     * @var WebhookService
     */
    private WebhookService $webhookService;

    /**
     * @since 1.1.0
     */
    private Client $stellarCommerceClient;

    /**
     * @since 1.1.0
     */
    public function __construct(
        AccountRepository $accountRepository,
        WebhookRepository $webhookRepository,
        WebhookService $webhookService,
        Client $stellarCommerceClient
    ) {
        $this->accountRepository = $accountRepository;
        $this->webhookRepository = $webhookRepository;
        $this->webhookService = $webhookService;
        $this->stellarCommerceClient = $stellarCommerceClient;
    }

    /**
     * @since 1.4.0 On webhook missing or expired apikey exception, continue account deletion.
     * @since 1.1.0
     *
     * @throws Exception|InvalidPropertyException|BindingResolutionException
     */
    public function __invoke(PaymentGatewayMode $paymentGatewayMode): void
    {
        $this->paymentGatewayMode = $paymentGatewayMode;

        // Setup stripe client.
        // This is required to use the Stripe client by mode instead of using the global Stripe client.
        $this->webhookService->setHttpClient(StripeClient::getClient($paymentGatewayMode));

        try {
            $webhook = $this->webhookRepository->getWebhook($this->paymentGatewayMode);
            if ($webhook) {
                $this->webhookService->deleteWebhook($webhook->getId());
            }

            $this->removeAccount();
        } catch (StripeAPIException $e) {
            if (! $e->isResourceNotFound() && ! $e->isPlatformApiKeyExpired()) {
                throw $e;
            }
        }

        // Remove webhook data from the website.
        $this->webhookRepository->deleteWebhook($this->paymentGatewayMode);

        // Remove the Stripe account from the website.
        $this->accountRepository->deleteAccount($this->paymentGatewayMode);
    }

    /**
     * @since 1.1.0
     *
     * @throws Exception|InvalidPropertyException
     */
    private function removeAccount(): void
    {
        $url = $this->stellarCommerceClient->getStripeDisconnectRequestUrl($this->paymentGatewayMode);
        $response = remote_get($url);

        if (is_wp_error($response)) {
            throw new Exception(esc_html($response->get_error_message()));
        }

        $errorMessage = esc_html__('Unable to delete the Stripe connection. Please try later.', 'stellarpay');

        if (200 !== wp_remote_retrieve_response_code($response)) {
            throw new Exception($errorMessage); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        try {
            $result = json_decode(wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new Exception($errorMessage); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if (! isset($result['stripe_disconnected']) || ! $result['stripe_disconnected']) {
            $newErrorMessage = isset($result['error_code'])
                ? sprintf(
                /* translators: 1: Error code */
                    esc_html__(
                        'Unable to delete the Stripe connection. Please try later. Error code is %1$s',
                        'stellarpay'
                    ),
                    $result['error_code']
                )
                : $errorMessage;

            throw new Exception($newErrorMessage); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
    }
}
