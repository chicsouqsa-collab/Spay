<?php

/**
 * This class is used to add the Subscriptions pages to the My Account page
 *
 * @package StellarPay\Integrations\WooCommerce\Views
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\MySubscriptionDTO;
use WC_Order;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates\StatusLabel;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService;

use function StellarPay\Core\container;

/**
 * @since 1.1.0
 */
class MySubscriptionsPage
{
    /**
     * @since 1.1.0
     */
    protected RenewalOrderRepository $renewalOrderRepository;

    /**
     * @since 1.4.1
     */
    protected PaymentIntentService $paymentIntentService;

    /**
     * @since 1.1.0
     */
    public function __construct(
        RenewalOrderRepository $renewalOrderRepository,
        PaymentIntentService $paymentIntentService
    ) {
        $this->renewalOrderRepository = $renewalOrderRepository;
        $this->paymentIntentService = $paymentIntentService;
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function __invoke()
    {
        Hooks::addAction(
            'woocommerce_account_' . MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG . '_endpoint',
            self::class,
            'addPageContent'
        );

        Hooks::addAction(
            'woocommerce_order_details_after_customer_details',
            self::class,
            'addSubscriptionsTableToViewOrderPage'
        );

        Hooks::addAction(
            'woocommerce_thankyou',
            self::class,
            'addSubscriptionsTableToOrderReceivedPage'
        );

        Hooks::addFilter(
            'woocommerce_endpoint_' . MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG . '_title',
            self::class,
            'updateTitle'
        );

        container(TestModeBadge::class)->enqueueAssets();
    }

    /**
     * Update title
     *
     * @since 1.1.0
     */
    public function updateTitle(string $title): string
    {
        if (!MySubscriptionsEndpoint::isPage()) {
            return $title;
        }

        $subscriptionQueryVars = MySubscriptionsEndpoint::getQueryVars();

        if (!empty($subscriptionQueryVars)) {
            return $title;
        }

        return esc_html__('Subscriptions', 'stellarpay');
    }

    /**
     * Add subscription page content.
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function addPageContent(): void
    {
        if (QueryVars::missing(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG)) {
            return;
        }

        $subscriptionQueryVars = QueryVars::get(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG);

        if (!empty($subscriptionQueryVars)) {
            return;
        }

        $this->addSubscriptionsPageContent();
    }

    /**
     * Add subscriptions page content
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    protected function addSubscriptionsPageContent(): void
    {
        $subscriptions = Subscription::query()
                ->where('customer_id', get_current_user_id())
                ->orderBy('created_at_gmt', 'DESC')
                ->getAll();

        if (!$subscriptions) {
            esc_html_e('No subscriptions', 'stellarpay');

            return;
        }

        $subscriptions = array_filter(
            array_map(
                [MySubscriptionDTO::class, 'fromSubscription'],
                $subscriptions
            ),
            static function (MySubscriptionDTO $subscription) {
                return (bool) $subscription->orderItem;
            }
        );

        $this->renderSubscriptionList($subscriptions);
    }

    /**
     * Output the subscription list table
     *
     * @since 1.1.0
     *
     * @param MySubscriptionDTO[] $subscriptions Subscription data objects.
     *
     * @throws BindingResolutionException
     */
    protected function renderSubscriptionList(array $subscriptions, string $title = ''): void
    {
        global $_wp_current_template_content; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

        $hasOrderConfirmationTotalsBlock = has_block('woocommerce/order-confirmation-totals', $_wp_current_template_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

        switch (true) {
            case $hasOrderConfirmationTotalsBlock && is_order_received_page():
                $tableClasses = 'wc-block-order-confirmation-totals__table';
                break;

            case QueryVars::has('view-order'):
            case !$hasOrderConfirmationTotalsBlock && is_order_received_page():
                $tableClasses = 'woocommerce-table woocommerce-table--order-details shop_table order_details';
                break;

            default:
                $tableClasses = 'woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table';
                break;
        }

        $subscriptionTitle = esc_html__('Subscription', 'stellarpay');
        $statusTitle = esc_html__('Status', 'stellarpay');
        $nextPaymentTitle = esc_html__('Next Payment', 'stellarpay');
        $totalTitle = esc_html__('Total', 'stellarpay');

        ?>
        <div id="stellarpay-subscriptions">
            <?php if (!empty($title)) : ?>
                <?php if (!$hasOrderConfirmationTotalsBlock) : ?>
                    <h2 class="sp-mt-4">
                        <?php echo esc_html($title); ?>
                    </h2>
                <?php endif; ?>

                <?php if ($hasOrderConfirmationTotalsBlock) : ?>
                    <h3 class="sp-mt-4">
                        <?php echo esc_html($title); ?>
                    </h3>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($hasOrderConfirmationTotalsBlock) : ?>
                <div class="wc-block-order-confirmation-totals">
            <?php endif; ?>

            <table class="<?php echo esc_attr($tableClasses); ?>">
                <thead>
                    <tr>
                        <th scope="col" class="woocommerce-orders-table__header">
                            <span>
                                <?php echo esc_html($subscriptionTitle); ?>
                            </span>
                        </th>
                        <th scope="col" class="woocommerce-orders-table__header">
                            <span>
                                <?php echo esc_html($statusTitle); ?>
                            </span>
                        </th>
                        <th scope="col" class="woocommerce-orders-table__header">
                            <span>
                                <?php echo esc_html($nextPaymentTitle); ?>
                            </span>
                        </th>
                        <th scope="col" class="woocommerce-orders-table__header">
                            <span>
                                <?php echo esc_html($totalTitle); ?>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription) : ?>
                        <tr>
                            <td
                                class="woocommerce-orders-table__cell"
                                data-title="<?php echo esc_attr($subscriptionTitle); ?>"
                            >
                                <?php $this->renderSubscriptionName($subscription->id, $subscription->orderItem->get_name('view')) ?>
                            </td>
                            <td
                                class="woocommerce-orders-table__cell"
                                data-title="<?php echo esc_attr($statusTitle); ?>"
                            >
                                <?php (new StatusLabel($subscription))->render(); ?>
                            </td>
                            <td
                                class="stellarpay-subscriptions__next-payment woocommerce-orders-table__cell"
                                data-title="<?php echo esc_attr($nextPaymentTitle); ?>"
                            >
                                <?php echo esc_html($subscription->nextBillingAt); ?>
                            </td>
                            <td
                                class="woocommerce-orders-table__cell"
                                data-title="<?php echo esc_attr($subscriptionTitle); ?>"
                            >
                                <div class="sp-inline-flex sp-flex-col">
                                    <div>
                                        <?php echo wp_kses_post($subscription->total); ?>
                                    </div>
                                    <div>
                                        <?php if ($subscription->frequency > 1) : ?>
                                            <span class="sp-font-normal sp-text-sm">
                                            <?php
                                            echo sprintf(
                                                // translators: %d number of total payments.
                                                esc_html__('(%d total payments)', 'stellarpay'),
                                                $subscription->frequency // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            );
                                            ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($subscription->isTestOrder) : ?>
                                            <?php container(TestModeBadge::class)->render(); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($hasOrderConfirmationTotalsBlock) : ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Output the subscriptions table based on the order
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    protected function addSubscriptionsTable(WC_Order $order): void
    {
        $subscriptions = Subscription::findAllByFirstOrderId($order->get_id());

        if (empty($subscriptions)) {
            $subscriptions = array_filter([
                $this->renewalOrderRepository->getRenewalSubscription($order)
            ]);
        }

        if (!$subscriptions) {
            return;
        }

        $subscriptions = array_filter(
            array_map(
                [MySubscriptionDTO::class, 'fromSubscription'],
                $subscriptions
            ),
            static function (MySubscriptionDTO $subscription) {
                return (bool) $subscription->orderItem;
            }
        );

        $this->renderSubscriptionList($subscriptions, esc_html__('Subscriptions', 'stellarpay'));
    }

    /**
     * Output the subscription table on the View Order page
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function addSubscriptionsTableToViewOrderPage(WC_Order $order): void
    {
        $this->addSubscriptionsTable($order);
    }

    /**
     * Output the subscriptions table on the Order Received (Thank You) page
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function addSubscriptionsTableToOrderReceivedPage(int $orderId): void
    {
        if (did_action('woocommerce_order_details_after_customer_details')) {
            return;
        }

        $order = wc_get_order($orderId);

        if (!is_a($order, WC_Order::class)) {
            return;
        }

        $this->addSubscriptionsTable($order);
    }

    /**
     * Output the subscription name with a link to the
     * product if available.
     *
     * @since 1.1.0
     */
    protected function renderSubscriptionName(int $id, string $name): void
    {
        printf(
            '<a href="%1$s" title="%2$s">%3$s</a>',
            esc_url(MySubscriptionsEndpoint::getSubscriptionURL($id)),
            esc_html__('View subscription', 'stellarpay'),
            esc_html($name)
        );
    }
}
