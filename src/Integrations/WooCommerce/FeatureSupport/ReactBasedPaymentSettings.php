<?php

/**
 * This class updates the payment method gateway when the feature
 * `reactify-classic-payments-settings` is enabled.
 *
 * @package StellarPay\Integrations\WooCommerce\FeatureSupport\ReactBasedPaymentSettings
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\FeatureSupport;

use StellarPay\Core\Contracts\RegisterSupport;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Integrations\WooCommerce\Stripe\PaymentGateway;
use StellarPay\Core\Constants as CoreConstants;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PluginSetup\Environment;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
class ReactBasedPaymentSettings implements RegisterSupport
{
    /**
     * @since 1.8.0
     */
    protected PaymentGateway $paymentGateway;

    /**
     * @since 1.8.0
     */
    private SettingRepository $settingRepository;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
        $this->settingRepository = container(SettingRepository::class);
    }

    /**
     * @since 1.8.0
     */
    public function registerSupport(): void
    {
        if (!$this->isReactifyPaymentsSettingsEnabled()) {
            return;
        }

        $this->paymentGateway->method_title = $this->getMethodTitle();
        $this->paymentGateway->method_description = $this->getMethodDescription();
        $this->paymentGateway->icon = $this->getIcon();
    }

    /**
     * @since 1.9.0
     */
    protected function isReactifyPaymentsSettingsEnabled(): bool
    {
        return Environment::wooCommerceVersionCompare('9.9.0', '>=') ||
        FeaturesUtil::feature_is_enabled('reactify-classic-payments-settings');
    }

    /**
     * @since 1.8.0
     */
    protected function getMethodDescription(): string
    {
        return esc_html__('The most comprehensive and powerful Stripe integration for WooCommerce.', 'stellarpay');
    }

    /**
     * @since 1.8.0
     */
    protected function getIcon(): string
    {
        if ($this->isWoocommerceAdminPaymentsProviderRestApiRequest()) {
            return esc_url(CoreConstants::$PLUGIN_URL . '/build/images/stripe-icon-s-bg-purple.svg');
        }

        return '';
    }

    /**
     * @since 1.8.0
     */
    protected function getMethodTitle(): string
    {
        if ($this->isWoocommerceAdminPaymentsProviderRestApiRequest()) {
            return esc_html__('Stripe Payments', 'stellarpay');
        }

        return $this->settingRepository->getPaymentGatewayTitle();
    }

    /**
     * @since 1.8.0
     */
    protected function isWoocommerceAdminPaymentsProviderRestApiRequest(): bool
    {
        if (!WC()->is_rest_api_request()) {
            return false;
        }

        $route = untrailingslashit(QueryVars::get('rest_route') ?? '');

        return '/wc-admin/settings/payments/providers' === $route;
    }
}
