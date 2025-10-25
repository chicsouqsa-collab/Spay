<?php

/**
 * This class is used to add the Update Payment Method page.
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\Hooks;
use StellarPay\Core\Constants;
use WC_Order_Item_Product;
use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Controllers\MyAccount\MySubscriptions;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates\MySubscriptionDetailsRows;

/**
 * @since 1.1.0
 */
class UpdatePaymentMethodMySubscriptionPage
{
    /**
     * @since 1.1.0
     */
    protected const UPDATE_PAYMENT_METHOD_ACTION = 'update-payment-method';

    /**
     * @since 1.1.0
     */
    protected Request $request;

    /**
     * @since 1.1.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

     /**
     * @since 1.1.0
     */
    public function __invoke()
    {
        Hooks::addAction(
            'woocommerce_account_' . MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG . '_endpoint',
            self::class,
            'addPageContent'
        );

        Hooks::addFilter(
            'woocommerce_get_breadcrumb',
            self::class,
            'updateBreadCrumbs'
        );

        Hooks::addFilter(
            'woocommerce_endpoint_' . MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG . '_title',
            self::class,
            'updateTitle'
        );
    }

    /**
     * Output the Update Payment Method page content.
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function addPageContent(): void
    {
        if (QueryVars::missing(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG)) {
            return;
        }

        $subscriptionQueryVars = MySubscriptionsEndpoint::getQueryVars();

        if (empty($subscriptionQueryVars)) {
            return;
        }

        if (empty($subscriptionQueryVars['subscriptionId']) || self::UPDATE_PAYMENT_METHOD_ACTION !== $subscriptionQueryVars['action']) {
            return;
        }

        $subscription = Subscription::find($subscriptionQueryVars['subscriptionId']);

        if (!$subscription) {
            MySubscriptionsEndpoint::invalidSubscriptionNotice();
            return;
        }

        try {
            $orderItem = new WC_Order_Item_Product($subscription->firstOrderItemId);
        } catch (\Exception $e) {
            MySubscriptionsEndpoint::invalidSubscriptionNotice();
            return;
        }

        $order = wc_get_order($orderItem->get_order_id());

        if (! $order || ! current_user_can('view_order', $order->get_id())) { // phpcs:ignore WordPress.WP.Capabilities
            MySubscriptionsEndpoint::invalidSubscriptionNotice();
            return;
        }

        $subscription = MySubscriptionDTO::fromSubscription($subscription);

        Hooks::addFilter(
            'woocommerce_available_payment_gateways',
            self::class,
            'filterInPaymentGatewaysForUpdatePaymentPage'
        );

        $availableGateways = WC()->payment_gateways()->get_available_payment_gateways();

        remove_filter('woocommerce_available_payment_gateways', [self::class, 'filterInPaymentGatewaysForUpdatePaymentPage']);

        if (count($availableGateways)) {
            current($availableGateways)->set_current();
        }

        $action = MySubscriptions::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME;

        ?>
        <div id="stellarpay-subscriptions">
            <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                <tbody>
                    <?php (new MySubscriptionDetailsRows($subscription))->render() ?>
                </tbody>
            </table>

            <?php // using #add_payment_method instead of #update_payment_method to increase the compatibility with themes ?>
            <form id="add_payment_method" method="post" class="sp-mt-3">
                <div id="payment" class="woocommerce-checkout-payment">
                    <ul class="wc_payment_methods payment_methods methods">
                        <?php foreach ($availableGateways as $gateway) : ?>
                            <?php wc_get_template('checkout/payment-method.php', [ 'gateway' => $gateway ]); ?>
                        <?php endforeach; ?>
                    </ul>

                    <div class="form-row">
                        <button
                            class="woocommerce-Button woocommerce-Button--alt button alt wp-element-button"
                            id="update-payment-method-button"
                            >
                            <?php esc_html_e('Update payment method', 'stellarpay'); ?>
                        </button>

                        <?php
                            wp_nonce_field(
                                "stellarpay-{$action}-{$subscription->customerId}-{$subscription->id}",
                                Constants::NONCE_NAME
                            );
                        ?>

                        <input type="hidden" name="action" value="<?php echo esc_attr($action) ?>" />
                        <input type="hidden" name="stellarpay_subscription_id" value="<?php echo esc_attr((string) $subscription->id); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Filter in payment gateways for Update Payment Method page
     *
     * Customer can switch payment method with in the stripe payment gateway
     * Customer is not allowed to change payment gateway.
     *
     * @since 1.1.0
     */
    public static function filterInPaymentGatewaysForUpdatePaymentPage(array $availablePaymentGateways): array
    {
        foreach ($availablePaymentGateways as $key => $gateway) {
            if (\StellarPay\Integrations\WooCommerce\Stripe\Constants::GATEWAY_ID !== $gateway->id) {
                unset($availablePaymentGateways[$key]);
            }
        }

        return $availablePaymentGateways;
    }

    /**
     * Update breadcrumbs for Update Payment Method page.
     *
     * @since 1.1.0
     */
    public function updateBreadCrumbs(array $crumbs): array
    {
        if (count($crumbs) < 2) {
            return $crumbs;
        }

        if (!MySubscriptionsEndpoint::isPage()) {
            return $crumbs;
        }

        $subscriptionQueryVars = MySubscriptionsEndpoint::getQueryVars();

        if (empty($subscriptionQueryVars)) {
            return $crumbs;
        }

        $subscriptionId = absint($subscriptionQueryVars['subscriptionId']);
        $action = $subscriptionQueryVars['action'];

        if (empty($subscriptionId) || self::UPDATE_PAYMENT_METHOD_ACTION !== $action) {
            return $crumbs;
        }

        $subscriptionTitle = sprintf(
            // translators: %d - the subscription ID.
            esc_html__('Subscription #%d', 'stellarpay'),
            $subscriptionId
        );

        $crumbs[2] = [
            $subscriptionTitle,
            esc_url(MySubscriptionsEndpoint::getSubscriptionURL($subscriptionId))
        ];

        $crumbs[3] = [
            esc_html__('Update payment method', 'stellarpay')
        ];

        return $crumbs;
    }

    /**
     * Update title
     *
     * @since 1.1.0
     */
    public function updateTitle($title): string
    {
        global $wp;

        if (!MySubscriptionsEndpoint::isPage()) {
            return $title;
        }

        $subscriptionQueryVars = MySubscriptionsEndpoint::getQueryVars();

        if (empty($subscriptionQueryVars)) {
            return $title;
        }

        if (self::UPDATE_PAYMENT_METHOD_ACTION  !== $subscriptionQueryVars['action']) {
            return $title;
        }

        return sprintf(
            // translators: %d - the subscription ID.
            esc_html__('Update payment method - Subscription #%d', 'stellarpay'),
            $subscriptionQueryVars['subscriptionId']
        );
    }

    /**
     * @since 1.1.0
     */
    public static function getActionURL(int $subscriptionId): string
    {
        return MySubscriptionsEndpoint::getActionURL(
            self::UPDATE_PAYMENT_METHOD_ACTION,
            $subscriptionId
        );
    }
}
