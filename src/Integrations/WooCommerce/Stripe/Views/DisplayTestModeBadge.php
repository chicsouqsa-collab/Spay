<?php

/**
 * This file is responsible for adding test mode label to different parts of the WooCommerce admin when display orders.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Core\Constants as CoreConstants;
use StellarPay\Integrations\WooCommerce\Traits\OrderUtilities;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;
use WC_Order;
use WC_Payment_Token;
use WC_Payment_Tokens;

use function StellarPay\Core\container;
use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Class DisplayTestModeBadge
 *
 * @since 1.0.0
 */
class DisplayTestModeBadge
{
    use OrderUtilities;

    /**
     * @since 1.7.0
     */
    protected function getTipText(): string
    {
        return esc_html__('This order was made in test mode. No real money was exchanged.', 'stellarpay');
    }

    /**
     * @since 1.0.0
     */
    public function addToOrderStatusColumnInListTable($column, $orderId): void
    {
        $allowedColumns = [ 'order_status', 'status'];

        if (!in_array($column, $allowedColumns)) {
            return;
        }

        $order = wc_get_order($orderId);

        if (! $this->validateTestOrder($order)) {
            return;
        }

        ?>
            <?php container(TestModeBadge::class)->withHelpToolTip($this->getTipText())->addMarginLeft()->render(); ?>
        <?php
    }

    /**
     * @since 1.7.0 Show tooltip
     * @since 1.0.0
     */
    public function addToOrderDetailPage(WC_Order $order): void
    {
        if (! $this->validateTestOrder($order)) {
            return;
        }

        $scriptId = 'stellarpay-display-test-mode-label';
        wp_register_script($scriptId, false, [], CoreConstants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        container(TestModeBadge::class)->enqueueAssets();
        $testModeBadge = container(TestModeBadge::class)->withHelpToolTip($this->getTipText())->getHTML();

        $script = sprintf(
            '
            jQuery(document).ready(function () {
                jQuery(\'#stellarpay-badge-container\').append(`%1$s`);
                jQuery( document.body ).trigger( \'init_tooltips\' );
            });',
            $testModeBadge
        );

        wp_add_inline_script($scriptId, $script);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function addWooAdminStylesheet(string $hook): void
    {
        // Get the current screen
        $screen = get_current_screen();

        // Check if the current screen is a WooCommerce screen
        if (strpos($screen->id, 'woocommerce') !== false || strpos($screen->id, 'wc') !== false) {
            $styleId = 'stellarpay-woocommerce-admin';
            $script = new EnqueueScript($styleId, "/build/$styleId.js");
            $script->register()->loadStyle()->enqueueStyle();
        }
    }

    /**
     * Note -
     * WooCommerce does not have a filter on which we can use to add the "Test Mode" label to
     * payment method token which saved for our payment gateway.
     * For this reason, we are using JavaScript logic.
     *
     * @since 1.0.0
     */
    public function addTestModeLabelInCustomerPaymentTokenList(bool $hasPaymentMethodTokens): void
    {
        if (! $hasPaymentMethodTokens) {
            return;
        }

        // Select test mode payment method tokens.
        $savedPaymentMethodTokens = array_filter(
            WC_Payment_Tokens::get_customer_tokens(get_current_user_id()),
            static function (WC_Payment_Token $paymentMethodToken) {
                if (Constants::GATEWAY_ID !== $paymentMethodToken->get_gateway_id('edit')) {
                    return false;
                }

                $paymentGatewayMode = new PaymentGatewayMode(
                    $paymentMethodToken->get_meta(dbMetaKeyGenerator('payment_method_mode', true))
                );

                if (! $paymentGatewayMode->isTest()) {
                    return false;
                }

                return true;
            }
        );

        if (! $savedPaymentMethodTokens) {
            return;
        }

        $paymentMethodsIds = [];
        foreach ($savedPaymentMethodTokens as $paymentMethodToken) {
            $paymentMethodsIds[] = $paymentMethodToken->get_id();
        }

        // We are using payment method ids to identify payment method token row.

        $scriptId = 'stellarpay-my-account-test-mode-label';
        wp_register_script($scriptId, false, [], CoreConstants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        $testModeBadge = container(TestModeBadge::class)->addMarginLeft()->getHTML();
        $testModePaymentMethodTokenIdsList = sprintf(
            '[%1s]',
            implode(',', $paymentMethodsIds)
        );
        $script = sprintf(
            '
            document.addEventListener(\'DOMContentLoaded\', function(){
                const testModePaymentMethodTokenIds = %1$s;

                testModePaymentMethodTokenIds.forEach((paymentMethodTokenId) => {
                    const deleteLinkSelector = document.querySelector(
                        ".woocommerce-PaymentMethod.payment-method-actions a[href*=\'/delete-payment-method/"
                        + paymentMethodTokenId
                        + "/\']"
                    );
                    const parent = deleteLinkSelector.closest(\'.payment-method\');

                    // If the test mode badge already exists, do not add it again.
                    if (parent.querySelector(\'.stellarpay-test-mode-badge \')) {
                        return;
                    }

                    const span = document.createElement(\'span\');
                    span.innerHTML = `%2$s`
                    parent.querySelector(\'.payment-method-method\').appendChild(span);
                });
            });',
            $testModePaymentMethodTokenIdsList,
            $testModeBadge
        );

        wp_add_inline_script($scriptId, $script);
    }
}
