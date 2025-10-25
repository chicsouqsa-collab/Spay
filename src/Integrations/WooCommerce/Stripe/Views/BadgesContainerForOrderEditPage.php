<?php

/**
 *
 * This class is responsible to create an HTML container on the Woocommerce edit order page.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\Core\Constants as CoreConstants;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;

/**
 * @since 1.0.0
 */
class BadgesContainerForOrderEditPage
{
    /**
     * @since 1.0.0
     */
    public function __invoke(\WC_Order $order): void
    {
        if (Constants::GATEWAY_ID !== $order->get_payment_method('edit')) {
            return;
        }

        $scriptId = 'stellarpay-woo-order-edit-badge-container';
        wp_register_script($scriptId, false, [], CoreConstants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        $headingSelector = $this->getHeadingSelector();

        $script = sprintf(
            '
            jQuery(document).ready(function () {
                jQuery(\'%1$s\').after(\'<span id="stellarpay-badge-container"></span>\');
            });
            ',
            esc_html($headingSelector)
        );

        wp_add_inline_script($scriptId, $script);
    }

    /**
     * @since 1.7.0
     */
    protected function getHeadingSelector()
    {
        $currentScreen = get_current_screen();
        $postType = $currentScreen->post_type ?? null;

        switch ($postType) {
            case 'shop_subscription':
                return '#woocommerce-subscription-data #order_data h2';

            default:
                return 'h2.woocommerce-order-data__heading';
        }
    }
}
