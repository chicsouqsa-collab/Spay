<?php

/**
 * This class handles the changes made to the Edit Order Page.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\OrderEditPage;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order_Item;
use WC_Product;

/**
 * @since 1.0.0
 */
class OrderEditPage
{
    /**
     * @since 1.0.0
     */
    protected RenewalOrderRepository $renewalOrderRepository;

    /**
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
    public function __invoke(int $itemId, WC_Order_Item $item, WC_Product $product = null): void
    {
        if (!$product || !is_a($item, 'WC_Order_Item_Product')) {
            return;
        }

        $subscription = Subscription::findByFirstOrderAndItemId(
            $item->get_order_id('edit'),
            absint($itemId)
        );

        if (empty($subscription)) {
            // Check whether the order is renewal.
            $subscription = $this->renewalOrderRepository->getRenewalSubscription($item->get_order());

            if (empty($subscription)) {
                return;
            }
        }

        $viewDataItems = $this->getSubscriptionViewDataItems($subscription);

        foreach ($viewDataItems as $viewDataItem) {
            printf(
                '<div class="stellarpay-edit-order-item-line-subscription">%1$s</div>',
                wp_kses_post(
                    sprintf(
                        '<strong>%1$s</strong>: %2$s',
                        $viewDataItem['label'],
                        $viewDataItem['html']
                    )
                )
            );
        }

        $this->loadAssets();
    }

    /**
     * @since 1.0.0
     */
    private function loadAssets(): void
    {
        $scriptId = 'stellarpay-woocommerce-order-edit-page';
        wp_register_style($scriptId, false); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style($scriptId);

        $style = '
        .stellarpay-edit-order-item-line-subscription{font-size: 0.92em; color: #888; margin-top: 5px;}
        .stellarpay-edit-order-item-line-subscription strong{font-weight: 700;}';

        wp_add_inline_style('stellarpay-woocommerce-order-edit-page', $style);
    }

    /**
     * @since 1.5.0 Link subscription transaction id to subscription details page.
     * @since 1.2.0
     */
    private function getSubscriptionViewDataItems(Subscription $subscription): array
    {
        $subscriptionIdHTML = sprintf(
            '<a href="%1$s" target="_blank" rel="external noreferrer noopener" title="%2$s">%3$s</a>',
            admin_url('admin.php?page=stellarpay#/subscriptions/page/1/' . $subscription->id),
            esc_attr__('Visit subscription details page', 'stellarpay'),
            "#{$subscription->id}"
        );

        return [
            'subscription' => [
                'label' => esc_html__('Subscription', 'stellarpay'),
                'html' =>  $subscriptionIdHTML
            ],
            'subscriptionStatus' => [
                'label' => esc_html__('Subscription status', 'stellarpay'),
                'html' =>  $subscription->getFormattedStatusLabel(),
            ],
            'nextPayment' => [
                'label' => esc_html__('Next payment', 'stellarpay'),
                'html' =>  $subscription->getFormattedNextBillingAt()
            ]
        ];
    }
}
