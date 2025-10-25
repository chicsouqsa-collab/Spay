<?php

/**
 * This class displays the Test Mode badge for WooSubscriptions.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;
use WC_Order;
use WC_Subscription;
use StellarPay\Core\Constants as CoreConstants;
use StellarPay\Integrations\WooCommerce\Traits\OrderUtilities;

use function StellarPay\Core\container;

/**
 * @since 1.7.0
 */
class DisplayTestModeBadge
{
    use OrderUtilities;

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function onSubscriptionsEndpoint(): void
    {
        Hooks::addFilter('woocommerce_get_formatted_subscription_total', __CLASS__, 'appendTestModeBadge', 50, 2);
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function appendTestModeBadge(string $formattedOrderTotal, WC_Subscription $wooSubscription): string
    {
        if (! $this->validSubscription($wooSubscription)) {
            return $formattedOrderTotal;
        }

        $testModeBadge = container(TestModeBadge::class)->addMarginLeft()->getHTML();

        return $formattedOrderTotal . $testModeBadge;
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function adminHeadBoot()
    {
        container(TestModeBadge::class)->enqueueAssets();
        Hooks::addFilter(
            'woocommerce_subscription_list_table_column_status_content',
            __CLASS__,
            'appendTestModeBadgeInSubscriptionListTable',
            50,
            2
        );
    }

    /**
     * @since 1.7.0
     */
    protected function getTipText(): string
    {
        return esc_html__('This subscription was made in test mode. No real money was exchanged.', 'stellarpay');
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function appendTestModeBadgeInSubscriptionListTable(string $columnContent, WC_Order $wooSubscription)
    {
        if (! $this->validSubscription($wooSubscription)) {
            return $columnContent;
        }

        $testModeBadge = container(TestModeBadge::class)->withHelpToolTip($this->getTipText())->getHTML();

        return str_replace('</mark>', '</mark>' . $testModeBadge, $columnContent);
    }

    /**
     * Adds the Test Mode badge in the Subscription Details table
     *
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function onSubscriptionDetailsTable(WC_Order $wooSubscription)
    {
        if (! $this->validSubscription($wooSubscription)) {
            return;
        }

        $testModeBadge = container(TestModeBadge::class)->addMarginLeft()->getHTML();

        $script = sprintf(
            '
            document.addEventListener(\'DOMContentLoaded\', function(){
                const subscriptionStatusElement = document.querySelector(\'table.subscription_details tr:first-child td:nth-child(2)\');

                if(subscriptionStatusElement) {
                    subscriptionStatusElement.innerHTML += `%1$s`;
                }
            });',
            $testModeBadge
        );

        wp_add_inline_script('wcs-view-subscription', $script);
    }

    /**
     * @since 1.7.0 Show tooltip
     * @since 1.0.0
     */
    public function addToWooSubscriptionDetailPage(WC_Order $order): void
    {
        if (! $this->validSubscription($order)) {
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
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    private function validSubscription(WC_Order $subscription): bool
    {
        if (!is_a($subscription, WC_Subscription::class)) {
            return false;
        }

        if (!$this->validateTestOrder($subscription)) {
            return false;
        }

        return true;
    }

    /**
     * @since 1.7.0
     */
    public function addAdminCustomCSS()
    {
        $data = '
            .woocommerce_page_wc-orders--shop_subscription .widefat .column-status { width: 180px }
            .subscription-status:has(+ .stellarpay-test-mode-badge) { margin-bottom: 7px; margin-right: 4px }
        ';

        wp_add_inline_style(
            'woocommerce_subscriptions_admin',
            $data
        );
    }
}
