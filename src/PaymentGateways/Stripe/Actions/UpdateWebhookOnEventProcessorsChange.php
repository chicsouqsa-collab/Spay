<?php

/**
 * This class is responsible for validating the webhook.
 *
 * Admin would see a notice if the webhook data is not valid for the site.
 *
 * @package StellarPay\PaymentGateways\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;
use StellarPay\PaymentGateways\Stripe\Services\WebhookService;
use StellarPay\PaymentGateways\Stripe\Traits\StripeClientHelpers;
use StellarPay\PaymentGateways\Stripe\ValueObjects\Webhook as WebhookValueObject;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookRegisterer;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;

use function sort;
use function StellarPay\Core\dbOptionKeyGenerator;
use function StellarPay\Core\sanitizeTextField;

/**
 * Class WebhookValidator
 *
 * @since 1.3.0 Refactor class
 * @since 1.1.0 implement StripeClientHelpers trait
 * @since 1.0.0
 */
class UpdateWebhookOnEventProcessorsChange
{
    use StripeClientHelpers;

    /**
     * @since 1.0.0
     */
    protected string $webhookMigrationErrorOptionKey;

    /**
     * @since 1.0.0
     */
    private WebhookRepository $webhookRepository;

    /**
     * @since 1.0.0
     */
    private WebhookRegisterer $webhookRegisterer;

    /**
     * @since 1.0.0
     */
    private WebhookService $webhookService;

    /**
     * @since 1.0.0
     */
    private array $modes;

    /**
     * @since 1.0.0
     */
    public function __construct(
        WebhookRepository $webhookRepository,
        WebhookRegisterer $webhookRegisterer,
        WebhookService $webhookService
    ) {
        $this->webhookRepository = $webhookRepository;
        $this->webhookRegisterer = $webhookRegisterer;
        $this->webhookService = $webhookService;

        $this->modes = [
            PaymentGatewayMode::live(),
            PaymentGatewayMode::test()
        ];

        $this->webhookMigrationErrorOptionKey = dbOptionKeyGenerator('webhook_migration_error');
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public function __invoke(): ?bool
    {
        $mode = PaymentGatewayMode::live();
        $webhook = $this->webhookRepository->getWebhook($mode);

        $this->webhookMigrationError();

        // Exit if: Webhook is not setup for live mode.
        if (! $webhook) {
            return null;
        }

        // Exit if: event processors are the same.
        if ($this->areEventsUnchanged($webhook)) {
            return false;
        }

        try {
            $this->updateWebhooks();
            delete_option($this->webhookMigrationErrorOptionKey);

            return true;
        } catch (Exception $e) {
            update_option(
                $this->webhookMigrationErrorOptionKey,
                sanitizeTextField($e->getMessage()),
                false
            );
        }

        return false;
    }

    /**
     * @since 1.0.0
     */
    public function webhookMigrationError(): void
    {
        $errorMessage = get_option($this->webhookMigrationErrorOptionKey, '');
        $validateErrorMessage = function () use ($errorMessage) {
            return ! empty($errorMessage);
        };

        AdminNotices::show(
            $this->webhookMigrationErrorOptionKey,
            sprintf(
                '%s %s',
                esc_html__('StellarPay unable to setup webhook.', 'stellarpay'),
                $errorMessage
            )
        )->autoParagraph()
            ->asError()
            ->ifUserCan('manage_options')
            ->when($validateErrorMessage);
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException|BindingResolutionException|Exception
     */
    private function updateWebhooks(): void
    {
        /* @var PaymentGatewayMode $mode Payment gateway mode. */
        foreach ($this->modes as $mode) {
            $webhook = $this->webhookRepository->getWebhook($mode);
            if (! $webhook) {
                continue;
            }

            $this->webhookService->setHttpClient($this->getStripeClient($mode));

            $webhook = $this->webhookService->updateWebhookEvents($webhook, $mode);
            $this->webhookRepository->saveWebhook($webhook, $mode);
        }
    }

    /**
     * @since 1.0.0
     */
    protected function areEventsUnchanged(WebhookValueObject $webhook): bool
    {
        $currentEvents = $this->webhookRegisterer->getEventIds();
        $webhookEvents = $webhook->getEvents();

        // Sort both arrays and compare them directly
        sort($currentEvents);
        sort($webhookEvents);

        return $currentEvents === $webhookEvents;
    }
}
