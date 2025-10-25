<?php

/**
 * This class is used to handle the My Account Orders page.
 *
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Traits\OrderUtilities;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
class ViewOrders
{
    use OrderUtilities;

    /**
     * Add the Test Mode badge in the Order Status column.
     *
     * WooCommerce checks if there is an action hooked, and if so,
     * WooCommerce skips the output of the default content. That way,
     * this function outputs the default content and the Test Mode badge
     * when necessary.
     *
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __invoke(WC_Order $order): void
    {
        echo esc_html(wc_get_order_status_name($order->get_status()));

        if ($this->validateTestOrder($order)) {
            container(TestModeBadge::class)->addMarginLeft()->render();
        }
    }
}
