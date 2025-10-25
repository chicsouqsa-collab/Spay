<?php

/**
 * This file is responsible for adding test mode label to different parts of the WooCommerce admin when display orders.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\Core\Constants as CoreConstants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use WC_Order;

/**
 * @since 1.0.0
 */
class DisplaySubscriptionOrderBadge
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    private RenewalOrderRepository $renewalOrderRepository;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(RenewalOrderRepository $renewalOrderRepository)
    {
        $this->renewalOrderRepository = $renewalOrderRepository;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function addToOrderNumberColumnInListTable($column, $orderId): void
    {
        if ('order_number' !== $column) {
            return;
        }

        $this->renderBadge(wc_get_order($orderId), true);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function addToOrderDetailPage(WC_Order $order): void
    {
        $scriptId = 'stellarpay-display-subscription-labels';
        wp_register_script($scriptId, false, [], CoreConstants::VERSION, ['in_footer' => true]);
        wp_enqueue_script($scriptId);

        ob_start();
        $this->renderBadge($order);
        $badge = ob_get_clean();

        if (! $badge) {
            return;
        }

        $badge = preg_replace('/\s+/', ' ', $badge);
        $badge = trim($badge);

        $script = sprintf(
            '
            jQuery(document).ready(function () {
                jQuery(\'#stellarpay-badge-container\').append(\'%1$s\');
            });',
            $badge
        );

        wp_add_inline_script($scriptId, $script);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function renderBadge(WC_Order $order, $subscriptionTooltip = false): void
    {
        if (Constants::GATEWAY_ID !== $order->get_payment_method('edit')) {
            return;
        }

        if ($this->isParentSubscriptionOrder($order)) {
            ?>
            <span class="stellarpay-subscription-order-badge stellarpay-subscription-order-badge--parent">
                <?php echo esc_html_x('Subscription', 'Order List Table', 'stellarpay'); ?>
                <span>
                    <?php
                    if ($subscriptionTooltip) {
                        echo wp_kses_post(
                            wc_help_tip(
                                _x(
                                    'This is the initial payment for Subscription. Subsequent payments will be marked as renewals until the subscription is canceled or fulfilled.',
                                    'Order List Table',
                                    'stellarpay'
                                ),
                            )
                        );
                    }
                    ?>
                </span>
            </span>
            <?php
        } elseif ($this->isRenewalSubscriptionOrder($order)) {
            ?>
            <span class="stellarpay-subscription-order-badge stellarpay-subscription-order-badge--renewal">
                <?php echo esc_html_x('Renewal', 'Order List Table', 'stellarpay'); ?>
            </span>
            <?php
        }
    }


    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function isParentSubscriptionOrder(WC_Order $order): bool
    {
        if ($this->isSubscription($order)) {
            return true;
        }

        return false;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function isRenewalSubscriptionOrder(WC_Order $order): bool
    {
        if ($this->renewalOrderRepository->getRenewalSubscription($order)) {
            return true;
        }

        return false;
    }
}
