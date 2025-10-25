<?php

/**
 * This class is responsible for generating the dashboard data that is primarily used in the admin dashboard react app.
 *
 * @package StellarPay\AdminDashboard\DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\DataTransferObjects;

use StellarPay\AdminDashboard\NoticeManager;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\SubscriptionCancelAt;
use StellarPay\Core\ValueObjects\RefundType;
use StellarPay\Integrations\StellarCommerce\Client;
use StellarPay\Integrations\Stripe\Client as StripeClient;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\Integrations\WooCommerce\Stripe\Views\EditPaymentGatewayDisplay;
use StellarPay\PaymentGateways\Stripe\Controllers\OptedInStripeAccountEmailController;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\RestApi\AccountSession;
use StellarPay\PaymentGateways\Stripe\RestApi\DisconnectStripeAccount;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\PluginSetup\Environment;
use WC_Blocks_Utils;

use function StellarPay\Core\container;
use function StellarPay\Core\isWebsiteOnline;

/**
 * Class DashboardDTO
 *
 * @since 1.8.0 Remove "RegisterDomainOwnershipFileController" data.
 * @since 1.0.0
 */
final class DashboardDTO
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.0.0
     */
    private Webhook $webhook;

    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    private Client $stellarCommerceClient;

    /**
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        AccountRepository $accountRepository,
        SettingRepository $settingRepository,
        Client $stellarCommerceClient,
        Webhook $webhook
    ) {
        $this->accountRepository = $accountRepository;
        $this->settingRepository = $settingRepository;
        $this->stellarCommerceClient = $stellarCommerceClient;
        $this->webhook = $webhook;
    }

    /**
     * @since 1.6.0 Add WooCommerce required notice.
     * @since 1.0.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public function __invoke(): array
    {
        $data = [
            'apiDashboardRoute' => Constants::PLUGIN_SLUG . '/admin-dashboard/v1',
            'apiRoute' => Constants::PLUGIN_SLUG . '/v1',
            'apiNonce' => wp_create_nonce('wp_rest'),
            'pluginURL' => Constants::$PLUGIN_URL,
            'isSslEnabled' => is_ssl(),
            'version' => Constants::VERSION,
            'settings' => $this->settingRepository->getAll(),
            'isWebsiteOnline' => absint(isWebsiteOnline()),
            'activePaymentGatewayMode' => $this->settingRepository->getPaymentGatewayMode()->getId(),
            'sslRequiredNotice' => NoticeManager::getSslRequiredNotice(),
            'wooCommerceRequiredNotice' => \StellarPay\PluginSetup\NoticeManager::getWooCommerceMissingNotice(),
            'adminEmail' => get_bloginfo('admin_email'),
            'subscriptionCancelAtOptions' => SubscriptionCancelAt::getOptions(),
            'subscriptionRefundOptions' => RefundType::getOptions(),
            'wpVersion' => get_bloginfo('version'),
        ];

        $data = $this->getStripeData($data);
        $data = $this->getGatewayImages($data);

        return $this->getWooCommerceData($data);
    }

    /**
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    private function getStripeData(array $data): array
    {
        $data['stripe'] = [
            'live' => [
                'connectURL' => $this->stellarCommerceClient->getStripeOnBoardingUrl(PaymentGatewayMode::live()),
                'disconnectURL' => DisconnectStripeAccount::getDisconnectRequestUrl(PaymentGatewayMode::live()),
                'webhookURL' => $this->webhook->getEndpointByMode(PaymentGatewayMode::live()),
                'createAccountSessionURL' => null,
                'modeConnection' => false,
                'isConnected' => $this->accountRepository->isLiveModeConnected(),
                'paymentMethodDomain' => null
            ],
            'test' => [
                'connectURL' => null,
                'disconnectURL' => DisconnectStripeAccount::getDisconnectRequestUrl(PaymentGatewayMode::test()),
                'modeConnection' => false,
                'createAccountSessionURL' => null,
                'isTestModeOnlyAccount' => false,
                'isConnected' => $this->accountRepository->isTestModeConnected(),
                'paymentMethodDomain' => null
            ],
            'isBothModesUseSameStripeAccount' => $this->accountRepository->isBothModesUseSameStripeAccount(),
            'optedInStripeAccountEmailRequestURL' => OptedInStripeAccountEmailController::getRequestURL(),
            'apiVersion' => StripeClient::STRIPE_API_VERSION,
        ];

        $data['stripe']['onBoardingError'] = null;
        if ($error = $this->accountRepository->getOnboardingError()) {
            $data['stripe']['onBoardingError'] = $error;
            $this->accountRepository->clearOnboardingError();
        }

        if ($data['stripe']['live']['isConnected']) {
            $liveAccount = $this->accountRepository->getAccount(PaymentGatewayMode::live());

            $data['stripe']['live']['accountId'] = $liveAccount->getAccountId();
            $data['stripe']['live']['accountName'] = $liveAccount->getAccountName();
            $data['stripe']['live']['accountCurrency'] = $liveAccount->getAccountCurrency();
            $data['stripe']['live']['accountCountry'] = $liveAccount->getAccountCountry();
            $data['stripe']['live']['statementDescriptor'] = $liveAccount->getStatementDescriptor();
            $data['stripe']['live']['accountLogo'] = $liveAccount->getAccountLogo();
            $data['stripe']['live']['accountIcon'] = $liveAccount->getAccountIcon();
            $data['stripe']['live']['publishableKey'] = $liveAccount->getPublishableKey();
            $data['stripe']['live']['createAccountSessionURL'] = AccountSession::getCreateAccountSessionRequestUrl(PaymentGatewayMode::live());
            $data['stripe']['test']['connectURL'] = $this->stellarCommerceClient->getStripeOnBoardingUrl(PaymentGatewayMode::test());

            if (isWebsiteOnline()) {
                $paymentMethodDomain = $this->accountRepository->getPaymentMethodDomain(PaymentGatewayMode::live());

                if ($paymentMethodDomain) {
                    $data['stripe']['live']['paymentMethodDomain'] = $paymentMethodDomain->toArray();
                }
            }
        }

        if ($data['stripe']['test']['isConnected']) {
            $testAccount = $this->accountRepository->getAccount(PaymentGatewayMode::test());

            $data['stripe']['test']['accountId'] = $testAccount->getAccountId();
            $data['stripe']['test']['accountName'] = $testAccount->getAccountName();
            $data['stripe']['test']['accountCurrency'] = $testAccount->getAccountCurrency();
            $data['stripe']['test']['accountCountry'] = $testAccount->getAccountCountry();
            $data['stripe']['test']['statementDescriptor'] = $testAccount->getStatementDescriptor();
            $data['stripe']['test']['accountLogo'] = $testAccount->getAccountLogo();
            $data['stripe']['test']['accountIcon'] = $testAccount->getAccountIcon();
            $data['stripe']['test']['createAccountSessionURL'] = AccountSession::getCreateAccountSessionRequestUrl(PaymentGatewayMode::test());
            $data['stripe']['test']['isTestModeOnlyAccount'] = $testAccount->isTestModeOnlyAccount();
            $data['stripe']['test']['publishableKey'] = $testAccount->getPublishableKey();

            if (isWebsiteOnline()) {
                $paymentMethodDomain = $this->accountRepository->getPaymentMethodDomain(
                    $this->accountRepository->isBothModesUseSameStripeAccount()
                        ? PaymentGatewayMode::live()
                        : PaymentGatewayMode::test()
                );

                if ($paymentMethodDomain) {
                    $data['stripe']['test']['paymentMethodDomain'] = $paymentMethodDomain->toArray();
                }
            }
        }

        return $data;
    }

    /**
     * @since 1.0.0
     */
    private function getWooCommerceData(array $data): array
    {
        $data['woocommerce'] = [
            'isWoocommerceActive' => Environment::isWoocommerceActive(),
            'hasMinimumWooCommerceVersion' => Environment::hasMinimumWooCommerceVersion(),
            'isPaymentGatewayEnabled' => $this->isPaymentGatewayActiveInWoocommerce()
        ];

        if ($data['woocommerce']['isWoocommerceActive']) {
            $data['woocommerce'] = array_merge(
                $data['woocommerce'],
                [
                    'currency' => get_woocommerce_currency(),
                    'currencySymbol' => get_woocommerce_currency_symbol(),
                    'currencyPosition' => get_option('woocommerce_currency_pos'),
                    'thousandsSeparator' => wc_get_price_thousand_separator(),
                    'decimalSeparator' => wc_get_price_decimal_separator(),
                    'numberDecimals' => wc_get_price_decimals(),
                    'wooVersion' => WC()->version,
                    'blockCheckout' => WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout'),
                    'paymentsPageUrl' => admin_url('admin.php?page=wc-settings&tab=checkout'),
                ]
            );
        }

        return $data;
    }

    /**
     * Add Payment Gateway icons to the data.
     *
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    private function getGatewayImages($data): array
    {
        if (!empty($data['stripe'])) {
            $data['gatewayImages'] = container(EditPaymentGatewayDisplay::class)->getGatewayImagesArray();
        }

        return $data;
    }
}
