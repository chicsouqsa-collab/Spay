<?php

/**
 * This class is used to handle the My Account -> View Order page.
 *
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Traits\OrderUtilities;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;
use StellarPay\Core\Constants;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.8.0
 */
class ViewOrder
{
    use OrderUtilities;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __invoke(WC_Order $order): void
    {
        if (!$this->validateTestOrder($order)) {
            return;
        }

        $scriptId = 'stellarpay-display-test-mode-badge';
        wp_register_script($scriptId, false, [], Constants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        $testModeBadge = container(TestModeBadge::class)->addMarginLeft()->getHTML();

        // WooCommerce does not provide a way to select order total amount column.
        // To add the test mode badge, to the total amount column, we should use the label of the order total column to find selector.
        $totalLabel = $order->get_order_item_totals()['order_total']['label'] ?? null;

        if (!$totalLabel) {
            return;
        }

        $script = sprintf(
            '
            document.addEventListener(\'DOMContentLoaded\', function(){
                const orderTotalElement = Array.from(document.querySelectorAll(\'tfoot th\'))
                    .find(th => th.textContent.trim() === `%1$s`)
                    ?.closest(\'tr\')
                    ?.querySelector(\'td\');

                if(orderTotalElement) {
                    orderTotalElement.innerHTML += `%2$s`;
                }
            });',
            $totalLabel,
            $testModeBadge
        );

        wp_add_inline_script($scriptId, $script);
    }
}
