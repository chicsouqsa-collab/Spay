<?php

/**
 * This trait is responsible for providing utility methods related to the Stripe payment gateway.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Traits;

use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Constants as WooCommerceStripeConstants;
use StellarPay\PluginSetup\Environment;
use WC_Order;

/**
 * Trait WooCommercePaymentGatewayUtilities
 *
 * @since 1.7.0 Rename trait
 * @since 1.0.0
 */
trait WooCommercePaymentGatewayUtilities
{
    /**
     * @since 1.0.0
     * @return bool
     */
    protected function isPaymentGatewayActiveInWoocommerce(): bool
    {
        $paymentGatewaySettingOptionName = 'woocommerce_' . WooCommerceStripeConstants::GATEWAY_ID . '_settings';
        $wcPaymentGatewayData = get_option($paymentGatewaySettingOptionName, []);

        return Environment::isWoocommerceActive()
               && is_array($wcPaymentGatewayData)
               && array_key_exists('enabled', $wcPaymentGatewayData)
               && 'yes' === $wcPaymentGatewayData['enabled'];
    }

    /**
     * @since 1.0.0
     */
    protected function isPaymentGatewayInactiveInWoocommerce(): bool
    {
        return ! $this->isPaymentGatewayActiveInWoocommerce();
    }

    /**
     * @since 1.0.0
     */
    protected function matchPaymentGatewayInOrder(WC_Order $order): bool
    {
        return Constants::GATEWAY_ID === $order->get_payment_method('edit');
    }

    /**
     * @since 1.0.0
     */
    protected function notMatchPaymentGatewayInOrder(WC_Order $order): bool
    {
        return ! $this->matchPaymentGatewayInOrder($order);
    }
}
