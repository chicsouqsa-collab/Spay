<?php

/**
 * @package StellarPay\PaymentGateways\Stripe\Controllers
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Controllers;

use StellarPay\Core\Constants;
use StellarPay\Core\Contracts\Controller;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Request;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Actions\AddNewWebsiteToStripeAccount;
use StellarPay\Integrations\StellarCommerce\Actions\MigrateStripeAccountToNewWebsite;
use StellarPay\PaymentGateways\Stripe\Actions\AddPaymentMethodDomain;
use StellarPay\PaymentGateways\Stripe\Actions\CreateWebhook;
use StellarPay\PaymentGateways\Stripe\Notices\SiteMigrationNotice;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\WebhookRepository;
use StellarPay\PaymentGateways\Stripe\Services\WebhookService;
use StellarPay\PaymentGateways\Stripe\Traits\StripeClientHelpers;
use StellarPay\PluginSetup\Migrations\StoreHomeUrlInOptionTable;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;

use function StellarPay\Core\container;
use function StellarPay\Core\isWebsiteOnline;

/**
 * @since 1.3.0
 */
class SiteMigrationActionController extends Controller
{
    use StripeClientHelpers;

    /**
     * @since 1.3.0
     * @var PaymentGatewayMode[]
     */
    private array $paymentGatewayModes;

    /**
     * @since 1.3.0
     */
    private SiteMigrationNotice $siteMigrationNotice;

    /**
     * @since 1.3.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.3.0
     */
    private WebhookRepository $webhookRepository;

    /**
     * @since 1.3.0
     */
    private WebhookService $webhookService;

    /**
     * @since 1.3.0
     */
    private CreateWebhook $createWebhook;

    /**
     * @since 1.3.0
     */
    public function __construct(
        Request $request,
        SiteMigrationNotice $siteMigrationNotice,
        AccountRepository $accountRepository,
        WebhookRepository $webhookRepository,
        WebhookService $webhookService,
        CreateWebhook $createWebhook
    ) {
        parent::__construct($request);

        $this->accountRepository = $accountRepository;
        $this->webhookRepository = $webhookRepository;
        $this->webhookService = $webhookService;
        $this->createWebhook = $createWebhook;
        $this->siteMigrationNotice = $siteMigrationNotice;
        $this->paymentGatewayModes = [PaymentGatewayMode::live(), PaymentGatewayMode::test() ];
    }

    /**
     * @since 1.3.0
     * @throws Exception
     */
    public function __invoke(): void
    {
        if (
            ! current_user_can('manage_options')
            || ! $this->accountRepository->isLiveModeConnected()
            || ! isWebsiteOnline()
            || ! is_ssl()
        ) {
            return;
        }

        $action = $this->request->get($this->siteMigrationNotice->getActionKey(), null);
        $allowedActions = array_keys($this->siteMigrationNotice->getActionsURL());

        if (! $action || ! in_array($action, $allowedActions)) {
            return;
        }

        try {
            if ('migrate-to-new-domain' === $action) {
                $this->configureStripeConnectionToOnlyNewDomain();
            } elseif ('set-up-both-domain' === $action) {
                $this->configureStripeConnectionToNewDomain();
            } elseif ('keep-existing-setup' === $action) {
                $this->updateHomeUrlInOptionTable();
            }

            $this->displaySuccessNotice();
        } catch (\Exception $exception) {
            $this->displayErrorNotice($exception);
        }
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    private function configureStripeConnectionToOnlyNewDomain(): void
    {
        $this->updateWebhooks();
        $this->addPaymentMethodDomain();
        $this->migrateStripeAccount();
        $this->updateHomeUrlInOptionTable();
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException|Exception|InvalidArgumentException
     */
    private function configureStripeConnectionToNewDomain(): void
    {
        $this->createWebhooks();
        $this->addPaymentMethodDomain();
        $this->addStripeAccount();
        $this->updateHomeUrlInOptionTable();
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    private function updateHomeUrlInOptionTable(): void
    {
        container(StoreHomeUrlInOptionTable::class)->run();
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    private function addPaymentMethodDomain(): void
    {
        $invokable = container(AddPaymentMethodDomain::class);
        $invokable();
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    private function migrateStripeAccount(): void
    {
        $allModes = [PaymentGatewayMode::live(), PaymentGatewayMode::test() ];
        foreach ($allModes as $mode) {
            $invokable = container(MigrateStripeAccountToNewWebsite::class);
            $invokable($mode);
        }
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException|Exception
     */
    private function addStripeAccount(): void
    {
        $allModes = [PaymentGatewayMode::live(), PaymentGatewayMode::test() ];
        foreach ($allModes as $mode) {
            $invokable = container(AddNewWebsiteToStripeAccount::class);
            $invokable($mode);
        }
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    private function createWebhooks(): void
    {
        foreach ($this->paymentGatewayModes as $paymentGatewayMode) {
            $webhook = $this->webhookRepository->getWebhook($paymentGatewayMode);

            if (! $webhook) {
                continue;
            }

            $this->webhookService->setHttpClient($this->getStripeClient($paymentGatewayMode));

            $invokable = $this->createWebhook;
            $invokable($paymentGatewayMode);
        }
    }

    /**
     * @since 1.3.0
     *
     * @throws BindingResolutionException|Exception|InvalidArgumentException
     */
    private function updateWebhooks(): void
    {
        foreach ($this->paymentGatewayModes as $paymentGatewayMode) {
            $webhook = $this->webhookRepository->getWebhook($paymentGatewayMode);

            if (! $webhook) {
                return;
            }

            $this->webhookService->setHttpClient($this->getStripeClient($paymentGatewayMode));
            $this->webhookService->updateWebhook($webhook, $paymentGatewayMode);
        }
    }

    /**
     * @since 1.3.0
     */
    private function displayErrorNotice(\Exception $exception): void
    {
        AdminNotices::show(
            Constants::slugPrefixed('_webhook_migration_error'),
            sprintf(
                '%s %s',
                esc_html__('StellarPay unable to process the Stripe account migrations. Error details: ', 'stellarpay'),
                $exception->getMessage()
            )
        )->autoParagraph()
            ->asError()
            ->ifUserCan('manage_options');
    }

    /**
     * @since 1.3.0
     */
    private function displaySuccessNotice(): void
    {
        AdminNotices::show(
            Constants::slugPrefixed('_webhook_migration_success'),
            esc_html__('StellarPay successfully processed the Stripe account migrations.', 'stellarpay')
        )->autoParagraph()
            ->asSuccess()
            ->ifUserCan('manage_options');
    }
}
