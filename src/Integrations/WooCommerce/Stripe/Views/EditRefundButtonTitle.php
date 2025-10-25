<?php

/**
 * This class uses to edit refund button title on order edit page in WordPress admin.
 *
 * By default, the refund button title uses payment gateway title,
 * which is the customer facing payment gateway name and admin can set to the desired value.
 *
 * On the refund button, the payment method title replaces it with the Stripe label for admin clarity.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\Core\Constants;
use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use WC_Order;

/**
 * @since 1.0.0
 */
class EditRefundButtonTitle
{
    use WooCommercePaymentGatewayUtilities;

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
        if (! $this->canProcess()) {
            return;
        }

        $scriptId = 'stellarpay-woocommerce-edit-refund-button-title';
        wp_register_script($scriptId, false, [], Constants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        $script = sprintf(
            '
            document.addEventListener(\'DOMContentLoaded\', () => {
                const woocommerceOrderItemselement = document.querySelector(\'#woocommerce-order-items\');

                woocommerceOrderItemselement.addEventListener(\'click\', function(event) {
                    const refundButton = document.querySelector(\'.refund-items\');

                    if (event.target !== refundButton) {
                        return;
                    }

                    const apiRefundButton = document.querySelector(\'.refund-actions button.do-api-refund\');

                    if( apiRefundButton ) {
                        apiRefundButton.childNodes[2].nodeValue = \' %1$s Stripe\';
                    }
                })
            })
            ',
            esc_js(esc_html__('via', 'stellarpay'))
        );

        wp_add_inline_script($scriptId, $script);
    }

    /**
     * @since 1.0.0
     */
    private function canProcess(): bool
    {
        $screen = get_current_screen();

        if (empty($screen) || ! in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'], true)) {
            return false;
        }

        if ('edit' !== $this->request->get('action')) {
            return false;
        }

        $orderId = absint($this->request->get('id'));

        if (empty($orderId)) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (! $order instanceof WC_Order) {
            return false;
        }

        return $this->matchPaymentGatewayInOrder($order);
    }
}
