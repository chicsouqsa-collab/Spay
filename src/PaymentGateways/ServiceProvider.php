<?php

/**
 * This class is used to manage the payment gateways.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\PaymentGateways\Stripe\Actions\OptInStripeAccountEmail;
use StellarPay\PaymentGateways\Stripe\Actions\UpdateWebhookOnEventProcessorsChange;
use StellarPay\PaymentGateways\Stripe\Controllers\OnBoardingRedirectController as StripeOnboardingRedirectController;
use StellarPay\PaymentGateways\Stripe\Controllers\OptedInStripeAccountEmailController;
use StellarPay\PaymentGateways\Stripe\Controllers\SiteMigrationActionController;
use StellarPay\PaymentGateways\Stripe\Migrations\EncodePaymentMethodDomainNameInOptionValue;
use StellarPay\PaymentGateways\Stripe\Migrations\EncodeWebhookURLInOptionValue;
use StellarPay\PaymentGateways\Stripe\Notices\SiteMigrationNotice;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\RestApi\AccountSession;
use StellarPay\PaymentGateways\Stripe\RestApi\DetachCustomerPaymentMethod;
use StellarPay\PaymentGateways\Stripe\RestApi\DisconnectStripeAccount;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\PaymentGateways\Stripe\Services\ServiceRegisterer;
use StellarPay\Core\ValueObjects\WebhookEventType;
use StellarPay\PaymentGateways\Stripe\Webhook\Events\AccountUpdated;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookRegisterer;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;

use function StellarPay\Core\container;
use function StellarPay\Core\isWebsiteOnline;

/**
 * Class ServiceProvider
 *
 * @since 1.8.0 Remove "RegisterDomainOwnershipFileController" class related action hooks.
 * @since 1.0.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(StripeOnboardingRedirectController::class);
        container()->singleton(ServiceRegisterer::class);
        container()->singleton(Webhook::class);
        container()->singleton(WebhookRegisterer::class);
        container()->singleton(PaymentMethodRepository::class);

        // Bootstrap Stripe services.
        container(ServiceRegisterer::class)->register();
    }

    /**
     * @since 1.0.0
     * @throws InvalidArgumentException|BindingResolutionException|InvalidPropertyException
     * @throws Exception
     */
    public function boot(): void
    {
        // Stripe account onboarding controller.
        Hooks::addAction(
            'load-toplevel_page_stellarpay',
            StripeOnboardingRedirectController::class
        );

        // Register the webhook event processor.
        container(WebhookRegisterer::class)->registerEventHandlers([
            WebhookEventType::ACCOUNT_UPDATED => AccountUpdated::class,
        ]);

        if (container(AccountRepository::class)->isLiveModeConnected()) {
            $this->registerApiRoutes();

            add_action('admin_init', static function () {
                if (current_user_can('manage_options') && isWebsiteOnline() && is_ssl()) {
                    $webhookValidator = container(UpdateWebhookOnEventProcessorsChange::class);
                    $webhookValidator();
                }
            });

            Hooks::addAction(
                'wp_ajax_' . OptInStripeAccountEmail::CRON_JOB_NAME,
                OptedInStripeAccountEmailController::class
            );

            Hooks::addAction(
                OptInStripeAccountEmail::CRON_JOB_NAME,
                OptInStripeAccountEmail::class
            );

            Hooks::addAction('admin_init', SiteMigrationActionController::class, '__invoke', 0);
            add_action('admin_init', [$this, 'setupSiteMigrationAdminNotices']);
        }

        container(MigrationsRegister::class)->addMigrations([
            EncodeWebhookURLInOptionValue::class,
            EncodePaymentMethodDomainNameInOptionValue::class
        ]);
    }

    /**
     * This function is used to register the API routes.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function registerApiRoutes(): void
    {
        $apiRoutes = [
            AccountSession::class,
            DetachCustomerPaymentMethod::class,
            DisconnectStripeAccount::class,
            Webhook::class
        ];

        foreach ($apiRoutes as $apiRoute) {
            Hooks::addAction('rest_api_init', $apiRoute, 'register');
        }
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function setupSiteMigrationAdminNotices(): void
    {
        $siteMigrationNotice = container(SiteMigrationNotice::class);
        $adminNotice = AdminNotices::show($siteMigrationNotice->id(), $siteMigrationNotice->getContent(),);

        $adminNotice->asWarning()
            ->ifUserCan('manage_options')
            ->when(fn() => $siteMigrationNotice->shouldShowNotice());
    }
}
