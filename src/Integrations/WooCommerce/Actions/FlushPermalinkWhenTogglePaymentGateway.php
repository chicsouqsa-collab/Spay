<?php

/**
 * This action class used to unpause flush permalink logic.
 *
 * We register few endpoints that depend upon weather or not payment gateway enabled in the Woocommerce.
 * To make these endpoints available without a manually reset permalink, we unpause flush permalink logic.
 *
 * @since 1.0.0
 * @package StellarPay\Integrations\WooCommerce\Actions
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Actions;

use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\PluginSetup\PluginManager;

/**
 * @since 1.0.0
 */
class FlushPermalinkWhenTogglePaymentGateway
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
        if (! wp_doing_ajax()) {
            return;
        }

        $gatewayId = wc_clean(wp_unslash($this->request->post('gateway_id')));
        $action = wc_clean(wp_unslash($this->request->post('action')));

        if ('woocommerce_toggle_gateway_enabled' !== $action) {
            return;
        }

        if (Constants::GATEWAY_ID !== $gatewayId) {
            return;
        }

        $paymentGateways = WC()->payment_gateways->payment_gateways(); // @phpstan-ignore-line
        if (! isset($paymentGateways[ $gatewayId ])) {
            return;
        }

        PluginManager::unpauseFlushPermalinks();
    }
}
