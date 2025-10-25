<?php

/**
 * This class is used to add the Subscription View page to My Account section.
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order_Item_Product;
use StellarPay\Core\Constants;
use StellarPay\Integrations\WooCommerce\Controllers\MyAccount\MySubscriptions;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates\MySubscriptionDetailsRows;
use WC_Order;

/**
 * @since 1.1.0
 */
class ViewMySubscriptionPage
{
    /**
     * @since 1.1.0
     */
    protected RenewalOrderRepository $renewalOrderRepository;

    /**
     * @since 1.1.0
     */
    public function __construct(RenewalOrderRepository $renewalOrderRepository)
    {
        $this->renewalOrderRepository = $renewalOrderRepository;
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
            'woocommerce_endpoint_' . MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG . '_title',
            self::class,
            'updateTitle'
        );
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

        if (! empty($subscriptionQueryVars['action'])) {
            return $title;
        }

        $subscriptionTitle = sprintf(
            // translators: %d - the subscription ID.
            esc_html__('Subscription #%d', 'stellarpay'),
            $subscriptionQueryVars['subscriptionId']
        );

        return $subscriptionTitle;
    }

    /**
     * Add view subscription page content
     *
     * @since 1.7.0 Remove logic that registers filter hook to overide the payment mehthod title.
     * @since 1.1.0
     *
     * @throws BindingResolutionException
     */
    public function addPageContent(): void
    {
        if (! QueryVars::has(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG)) {
            return;
        }

        $subscriptionQueryVars = QueryVars::get(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG);

        if (empty($subscriptionQueryVars)) {
            return;
        }

        $subscriptionData = explode('/', $subscriptionQueryVars);
        $subscriptionId = absint($subscriptionData[0] ?? 0);
        $action = $subscriptionData[1] ?? '';

        if (! empty($action)) {
            return;
        }

        $subscription = Subscription::find($subscriptionId);

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

        $this->renderSubscriptionDetails($subscription);
        $this->relatedOrders($subscription);
    }

    /**
     * Output the view subscription table
     *
     * @since 1.1.0
     */
    protected function renderSubscriptionDetails(MySubscriptionDTO $subscription): void
    {
        $userId = get_current_user_id();

        if (! $userId) {
            MySubscriptionsEndpoint::invalidSubscriptionNotice();
            return;
        }

        $shouldShowUpdateButton = !$subscription->status->isPending();
        $shouldShowPauseButton = false;
        $shouldShowCancelButton = $subscription->canCancel && $subscription->isSubscriptionPayments;
        $cancelURL = MySubscriptionsEndpoint::getActionNonceURL(MySubscriptions::CANCEL_SUBSCRIPTION_ACTION_NAME, $subscription->id);
        $shouldShowActions = $subscription->canUpdate && ($shouldShowUpdateButton || $shouldShowCancelButton);

        ?>
        <div id="stellarpay-subscriptions">
            <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                <tbody>
                    <?php (new MySubscriptionDetailsRows($subscription))->render() ?>

                    <?php if ($shouldShowActions) : ?>
                        <tr>
                            <th>
                                <?php esc_html_e('Actions', 'stellarpay'); ?>
                            </th>
                            <td>
                                <div class="sp-flex sp-flex-wrap sp-gap-4 sp-items-center">
                                    <?php if ($shouldShowUpdateButton) : ?>
                                        <a
                                            href="update-payment-method"
                                            class="sp-flex sp-bg-gray-700 hover:sp-bg-gray-900 sp-focus-visible:outline sp-focus-visible:outline-2 sp-focus-visible:outline-offset-2 sp-focus-visible:outline-indigo-600 sp-rounded-md sp-px-4 sp-py-2 sp-text-sm sp-font-normal sp-text-white sp-shadow-sm sp-no-underline"
                                        >
                                            <?php echo esc_html__('Update payment method', 'stellarpay'); ?>

                                            <img
                                                src="<?php echo esc_url(Constants::$PLUGIN_URL . '/build/images/credit-card.svg'); ?>"
                                                class="sp-ml-1.5"
                                            />
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($shouldShowPauseButton) : // @phpstan-ignore-line ?>
                                        <a
                                            href="#"
                                            class="sp-flex sp-bg-orange-500 hover:sp-bg-orange-600 sp-focus-visible:outline sp-focus-visible:outline-2 sp-focus-visible:outline-offset-2 sp-focus-visible:outline-orange-600 sp-rounded-md sp-px-4 sp-py-2 sp-text-sm sp-font-normal sp-text-white sp-shadow-sm sp-no-underline"
                                        >
                                            <?php echo esc_html__('Pause subscription', 'stellarpay'); ?>

                                            <img
                                                src="<?php echo esc_url(Constants::$PLUGIN_URL . '/build/images/x-circle.svg'); ?>"
                                                class="sp-ml-1.5"
                                            />
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($shouldShowCancelButton) : ?>
                                        <a
                                            href="<?php echo esc_url($cancelURL); ?>"
                                            class="stellarpay-subscriptions__cancel-button sp-flex sp-bg-red-700 hover:sp-bg-red-900 sp-focus-visible:outline sp-focus-visible:outline-2 sp-focus-visible:outline-offset-2 sp-focus-visible:outline-red-600 sp-rounded-md sp-px-4 sp-py-2 sp-text-sm sp-font-normal sp-text-white sp-shadow-sm sp-no-underline"
                                        >
                                            <?php echo esc_html__('Cancel subscription', 'stellarpay'); ?>

                                            <img
                                                src="<?php echo esc_url(Constants::$PLUGIN_URL . '/build/images/x-circle.svg'); ?>"
                                                class="sp-ml-1.5"
                                            />
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
    }

    /**
     * Output the related orders.
     *
     * @since 1.1.0
     */
    protected function relatedOrders(MySubscriptionDTO $subscriptionDTO): void
    {
        $order = $subscriptionDTO->order;

        $relatedOrders = wc_get_orders([
            'parent' => $order->get_id(),
            'limit' => -1,
            'order' => 'ASC',
            'meta_key' => $this->renewalOrderRepository->getRenewalSubscriptionIdKey(),
            'meta_value' => $subscriptionDTO->id, // phpcs:ignore WordPress.DB.SlowDBQuery
        ]);

        ?>
        <section class="woocommerce-customer-details">
            <div id="stellarpay-subscriptions">
                <h2>
                    <?php esc_html_e('Orders', 'stellarpay'); ?>
                </h2>
                <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'stellarpay'); ?></th>
                            <th><?php esc_html_e('Date', 'stellarpay'); ?></th>
                            <th><?php esc_html_e('Status', 'stellarpay'); ?></th>
                            <th><?php esc_html_e('Total', 'stellarpay'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $this->orderRowData($order); ?>

                        <?php foreach ($relatedOrders as $relatedOrder) : ?>
                            <?php $this->orderRowData($relatedOrder); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php
    }

    /**
     * Output the order row data.
     *
     * @since 1.1.0
     */
    protected function orderRowData(WC_Order $order): void
    {
        ?>
            <tr>
                <td>
                    <?php echo absint($order->get_id()); ?>
                </td>
                <td>
                    <?php echo esc_html($order->get_date_created()->format(get_option('date_format', 'F j, Y'))); ?>
                </td>
                <td>
                    <?php
                        echo esc_html(wc_get_order_status_name($order->get_status()));
                    ?>
                </td>
                <td>
                    <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                </td>
                <td>
                    <?php $this->viewOrderButton($order); ?>
                </td>
            </tr>
        <?php
    }

    /**
     * Output the View Order button
     *
     * @since 1.1.0
     */
    protected function viewOrderButton(WC_Order $order): void
    {
        ?>
        <a
            href="<?php echo esc_url($order->get_view_order_url()); ?>"
            class="sp-flex sp-justify-center sp-bg-gray-700 hover:sp-bg-gray-900 sp-focus-visible:outline sp-focus-visible:outline-2 sp-focus-visible:outline-offset-2 sp-focus-visible:outline-indigo-600 sp-rounded-md sp-px-4 sp-py-2 sp-text-sm sp-font-normal sp-text-white sp-shadow-sm sp-no-underline"
            aria-label="<?php
                echo esc_attr(sprintf(
                    // translators: %d - the subscription ID
                    esc_html__('View subscription number %d', 'stellarpay'),
                    $order->get_id()
                ));?>"
        >
            <?php esc_html_e('View', 'stellarpay'); ?>

            <img
                src="<?php echo esc_url(Constants::$PLUGIN_URL . '/build/images/arrow-right.svg'); ?>"
                class="sp-ml-1.5"
            />
        </a>
        <?php
    }
}
