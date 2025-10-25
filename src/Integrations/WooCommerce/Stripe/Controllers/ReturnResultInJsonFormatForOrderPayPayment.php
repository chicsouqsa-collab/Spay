<?php

/**
 * This class is responsible for returning the response in json format for payments on the "order-pay" checkout page.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use WC_Order;

/**
 * Class ReturnResultInJsonFormatForOrderPayPayment
 *
 * @since 1.0.0
 */
class ReturnResultInJsonFormatForOrderPayPayment
{
    /**
     * @since 1.0.0
     */
    private Request $request;

    /**
     * ReturnResultInJsonFormatForOrderPayPayment constructor.
     *
     * @since 1.0.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Prevents the redirect on successful order payment.
     *
     * @since 1.0.0
     *
     * @return array|void
     */
    public function successResponse(array $results, int $orderId)
    {
        $order = wc_get_order($orderId);

        if (! $this->canProcessRequest($order)) {
            return $results;
        }

        wp_send_json($results);
    }

    /**
     * Prevents the redirect on failed order payment.
     *
     * @since 1.0.0
     */
    public function errorResponse(WC_Order $order): void
    {
        if (! $this->canProcessRequest($order)) {
            return;
        }

        $result = [
            'result' => 'failure',
            'message' => $this->getWooCommerceErrorAsString(),
            'redirect' => '',
            'retry' => true
        ];

        wc_clear_notices();

        wp_send_json($result);
    }

    /**
     * This method is responsible for catching the void return to return it as a json response.
     *
     * @since 1.0.0
     */
    public function catchVoidReturn(WC_Order $order): void
    {
        // Fire this action after WooCommerce `pay_action` execution completes,
        // to cache void return, because it can be an error that we need to catch.
        // This will be returned as json response.
        // This WC_Form_Handler::pay_action executes on the "wp" action hook with priority 20.
        add_action(
            'wp',
            function () use ($order) {
                $this->errorResponse($order);
            },
            21
        );
    }

    /**
     * Checks if the request can be processed.
     *
     * @since 1.0.0
     */
    private function canProcessRequest(WC_Order $order): bool
    {
        $selectedPaymentMethodId = $this->request->post('payment_method');

        // If the payment method is not ours, then return.
        if (Constants::GATEWAY_ID !== $selectedPaymentMethodId) {
            return false;
        }

        // This handles only requests from the "order-pay" checkout page.
        // The "woocommerce_payment_successful_result" filter hook also fires on the legacy checkout page.
        if (! is_wc_endpoint_url('order-pay')) {
            return false;
        }

        return true;
    }

    /**
     * This function returns WooCommerce error messages as a string.
     *
     * @since 1.0.0
     */
    private function getWooCommerceErrorAsString(): string
    {
        $notices = wc_get_notices('error');
        $errors = '';

        foreach ($notices as $notice) {
            $errors .= " {$notice['notice']}";
        }

        return $errors;
    }
}
