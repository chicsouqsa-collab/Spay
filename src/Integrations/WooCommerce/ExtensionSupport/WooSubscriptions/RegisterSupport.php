<?php

/**
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions\CopyStellarPayOrderMetadataToSubscriptionMetadata;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions\DisplayTestModeBadge;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions\EditPaymentMethodTitle;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions\ProcessRenewal;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions\SelectDefaultToken;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\PaymentGateway;
use StellarPay\Integrations\WooCommerce\Stripe\Views\BadgesContainerForOrderEditPage;
use StellarPay\PluginSetup\Environment;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Controllers\UpdatePaymentMethod;

/**
 * @since 1.7.0
 */
class RegisterSupport implements \StellarPay\Core\Contracts\RegisterSupport
{
    /**
     * @since 1.7.0
     */
    protected PaymentGateway $paymentGateway;

    /**
     * @since 1.7.0
     *
     * @param PaymentGateway $paymentGateway
     */
    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * Initiate the subscription.
     *
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function registerSupport(): void
    {
        if (Environment::isWooSubscriptionActive()) {
            $this->paymentGateway->supports = array_merge(
                $this->paymentGateway->supports,
                $this->getSubscriptionFeatures()
            );
            $this->addHooks();
        }
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    protected function addHooks()
    {
        // Edit payment method title.
        Hooks::addFilter(
            'woocommerce_subscription_payment_method_to_display',
            EditPaymentMethodTitle::class,
            '__invoke',
            10,
            3
        );

        // Handle renewal payments.
        Hooks::addAction(
            'woocommerce_scheduled_subscription_payment_' . Constants::GATEWAY_ID,
            ProcessRenewal::class,
            '__invoke',
            10,
            2
        );


        // This must run after order pre-processor runs.
        // Order pre-processor will create customer and payment method id for supported payment gateway.
        // Customer and payment method id is critical to process subscription renewal.
        // If we don't have customer and payment method id, then we can't process the subscription renewal.
        // The Woocommerce subscription extension copies most of the subscription metadata to create a renewal.
        // For this reason, we copy all existing "stellarpay" order metadata to the subscription metadata.
        Hooks::addAction(
            'woocommerce_rest_checkout_process_payment_with_context',
            CopyStellarPayOrderMetadataToSubscriptionMetadata::class,
            '__invoke',
            99,
            1
        );

        // Update the payment method id in the subscription meta.
        Hooks::addFilter(
            'stellarpay_payment_process',
            UpdatePaymentMethod::class,
            '__invoke',
            10,
            2
        );

        // When viewing the subscription update payment method page, make sure the correct token is selected.
        Hooks::addFilter(
            'woocommerce_payment_token_cc_get_is_default',
            SelectDefaultToken::class,
            '__invoke',
            10,
            2
        );

        // This must run after order pre-processor runs.
        // Order pre-processor will create customer and payment method id for supported payment gateway.
        // Customer and payment method id is critical to process subscription renewal.
        // If we don't have customer and payment method id, then we can't process the subscription renewal.
        // The Woocommerce subscription extension copies most of the subscription metadata to create a renewal.
        // For this reason, we copy all existing "stellarpay" order metadata to the subscription metadata.
        Hooks::addAction(
            'woocommerce_rest_checkout_process_payment_with_context',
            CopyStellarPayOrderMetadataToSubscriptionMetadata::class,
            '__invoke',
            99,
            1
        );

        // Show Test Mode Badge in the customer Subscriptions view
        Hooks::addAction(
            'woocommerce_account_subscriptions_endpoint',
            DisplayTestModeBadge::class,
            'onSubscriptionsEndpoint',
            5
        );
        Hooks::addAction(
            'woocommerce_subscription_details_table',
            DisplayTestModeBadge::class,
            'onSubscriptionDetailsTable',
            50
        );

        // Show Test Mode Badge in admin Subscriptions
        Hooks::addAction(
            'admin_head-woocommerce_page_wc-orders--shop_subscription',
            DisplayTestModeBadge::class,
            'adminHeadBoot'
        );
        Hooks::addAction(
            'admin_enqueue_scripts',
            DisplayTestModeBadge::class,
            'addAdminCustomCSS',
            50
        );

        // Show Test Mode badge in the admin subscription detail page
        Hooks::addAction(
            'woocommerce_admin_order_data_after_order_details',
            BadgesContainerForOrderEditPage::class,
            '__invoke',
            5
        );
        Hooks::addAction(
            'woocommerce_admin_order_data_after_order_details',
            DisplayTestModeBadge::class,
            'addToWooSubscriptionDetailPage'
        );
    }

    /**
     * Get the subscription features.
     *
     * @since 1.7.0
     */
    protected function getSubscriptionFeatures(): array
    {
        return [
            'subscriptions',
            'multiple_subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_date_changes',
            'subscription_amount_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',

            // Woocommerce Subscription will take care of renewals for subscription.
            // Each renewal will be processed as off session one fold payment on the Stripe.
            //'gateway_scheduled_payments',
        ];
    }
}
