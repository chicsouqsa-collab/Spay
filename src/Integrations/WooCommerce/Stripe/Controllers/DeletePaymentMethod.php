<?php

/**
 * This class is the controller for "delete payment method" requests from customer.
 *
 * Note:
 * This controller should run before the WooCommerce request controller (WC_Form_Handler::delete_payment_method_action).
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use Exception;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use WC_Payment_Tokens;
use StellarPay\Core\Request ;

/**
 * @since 1.0.0
 */
class DeletePaymentMethod
{
    /**
     * @since 1.0.0
     */
    private PaymentMethodService $paymentMethodService;

    /**
     * @since 1.0.0
     */
    private Request $request;

    /**
     * @since 1.0.0
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        Request $request
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->request = $request;
    }

    /**
     * @since 1.0.0
     */
    public function __invoke(): void
    {
        if (! QueryVars::missing('delete-payment-method')) {
            return;
        }

        wc_nocache_headers();

        $tokenId = QueryVars::getInt('delete-payment-method');
        $token = WC_Payment_Tokens::get($tokenId);
        $nonce = sanitize_text_field(wp_unslash($this->request->get('_wpnonce')));

        if (
            ! $nonce
            || ! $token
            || Constants::GATEWAY_ID !== $token->get_gateway_id('edit')
            || false === wp_verify_nonce($nonce, 'delete-payment-method-' . $tokenId)
            || get_current_user_id() !== $token->get_user_id()
        ) {
            return;
        }

        try {
            $this->paymentMethodService->detachPaymentMethod($token->get_token());
        } catch (Exception $e) {
            wc_add_notice(esc_html__('We are sorry, but we could not delete your payment method at this time. Please try again shortly. If the issue continues to occur, do not hesitate to contact our support team for assistance', 'stellarpay'),);
            wp_safe_redirect(wc_get_account_endpoint_url('payment-methods'));
            exit();
        }
    }
}
