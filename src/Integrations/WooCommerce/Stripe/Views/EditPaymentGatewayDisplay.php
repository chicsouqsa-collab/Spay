<?php

/**
 * This class used to manager rendering of the payment gateway in WooCommerce settings.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\Core\Constants as CoreConstants;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

/**
 * @since 1.0.0
 */
class EditPaymentGatewayDisplay
{
    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * Class constructor.
     *
     * @param AccountRepository $accountRepository
     *
     * @since 1.0.0
     */
    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function makeChangeToDOMInWooPaymentGatewayList(): void
    {
        $isStripeConnected = $this->accountRepository->isLiveModeConnected();
        $gatewayImages = $this->getGatewayImagesArray();
        $gatewaySettingPageLink = admin_url('admin.php?page=stellarpay#/settings/appearance');
        $dashboardPageLink = admin_url('admin.php?page=stellarpay#/dashboard');
        $pageSlug = CoreConstants::PLUGIN_SLUG;
        $actionButtonTitleWhenStripeConnected = esc_html__('Manage Integration', 'stellarpay');

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $stellarPayDescription = esc_html__('The most comprehensive and powerful Stripe integration for WooCommerce.', 'stellarpay');

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $powerByStripeImage = CoreConstants::$PLUGIN_URL . '/build/images/powered-by-stripe-purple.png';
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $stripeVendorImage = CoreConstants::$PLUGIN_URL . '/build/images/stripe-verified-partner-badge.png';

        $scriptId = 'stellarpay-woocommerce-settings-payments';

        $script = new EnqueueScript($scriptId, "/build/$scriptId.js");
        $script->loadInFooter()
            ->registerLocalizeData(
                'wooSettingsStellarPayIntegrationData',
                [
                    'isStripeConnected' => $isStripeConnected,
                    'gatewayId' => Constants::GATEWAY_ID,
                    'gatewayImages' => $gatewayImages,
                    'gatewaySettingPageLink' => $gatewaySettingPageLink,
                    'dashboardPageLink' => $dashboardPageLink,
                    'pageSlug' => $pageSlug,
                    'actionButtonTitleWhenStripeConnected' => $actionButtonTitleWhenStripeConnected,
                    'stellarPayDescription' => $stellarPayDescription,
                    'powerByStripeImage' => $powerByStripeImage,
                    'stripeVendorImage' => $stripeVendorImage,
                    'pluginUrl' => CoreConstants::$PLUGIN_URL,
                ]
            )
            ->register()
            ->enqueue();
    }

    /**
     * @since 1.0.0
     */
    public function getGatewayImagesArray(): array
    {
        return [
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/Visa.png',
                'alt' => esc_html__('Visa logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/mastercard.png',
                'alt' => esc_html__('Mastercard logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/AmericanExpress.png',
                'alt' => esc_html__('American Express logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/ApplePay.png',
                'alt' => esc_html__('Apple Pay logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/GooglePay.png',
                'alt' => esc_html__('Google Pay logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/Klarna.png',
                'alt' => esc_html__('Klarna logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/AmazonPay.png',
                'alt' => esc_html__('Amazon Pay logo', 'stellarpay'),
            ],
            [
                'src' => CoreConstants::$PLUGIN_URL . '/build/images/Discover.png',
                'alt' => esc_html__('Discover logo', 'stellarpay'),
            ],
        ];
    }
}
