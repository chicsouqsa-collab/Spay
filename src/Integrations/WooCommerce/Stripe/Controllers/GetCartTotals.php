<?php

/**
 * This class handles a request for wc_ajax=get_cart_totals&payment-method=stellarpay-stripe.
 *
 * Woocommerce does not provide a way to access cart total on the legacy checkout page and order pay page.
 * This ajax endpoint is used to get card total.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;

/**
 * @since 1.0.0
 */
class GetCartTotals
{
    /**
     * @since 1.0.0
     */
    protected Request $request;

    /**
     * @since 1.0.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @since 1.0.0
     */
    public function __invoke()
    {
        $paymentMethod = $this->request->get('payment-method');
        $action = $this->request->get('action');
        $orderKey = $this->request->get('order-key');

        if ('get_cart_totals' !== $action || Constants::GATEWAY_ID !== $paymentMethod) {
            return;
        }

        $currency = strtolower(get_woocommerce_currency());

        if ($orderKey) {
            $order = wc_get_order(wc_get_order_id_by_order_key($orderKey));
            $amount = Money::make((float) $order->get_total('edit'), $currency);
        } else {
            wc_maybe_define_constant('WOOCOMMERCE_CART', true);

            $cart = WC()->cart;

            $cart->calculate_totals();
            $amount = Money::make((float)$cart->get_total('edit'), $currency);
        }

        wp_send_json_success([
            'totalMinorAmount' => $amount->getMinorAmount(),
        ]);
    }
}
