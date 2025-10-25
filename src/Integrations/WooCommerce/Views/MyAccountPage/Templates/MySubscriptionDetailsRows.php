<?php

/**
 * This class is responsible for render the subscription details rows.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates;

use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\MySubscriptionDTO;
use StellarPay\Core\Contracts\View;
use StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge\TestModeBadge;

use function StellarPay\Core\container;

/**
 * MySubscriptionDetailsRows class
 *
 * @since 1.1.0
 */
class MySubscriptionDetailsRows extends View
{
    /**
     * @since 1.1.0
     */
    protected MySubscriptionDTO $subscription;

    /**
     * Class constructor
     *
     * @since 1.1.0
     */
    public function __construct(MySubscriptionDTO $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Output the subscription details rows
     *
     * @since 1.1.0
     */
    public function getHTML(): string
    {
        ob_start();
        ?>
        <tr>
            <th>
                <?php esc_html_e('Product', 'stellarpay'); ?>
            </th>
            <td class="woocommerce-table__product-name product-name">
                <?php $this->renderProductName($this->subscription->orderItem); ?>

                <?php if ($this->subscription->isTestOrder) : ?>
                    <?php container(TestModeBadge::class)->addMarginLeft()->render(); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>
                <?php esc_html_e('Renewal Date', 'stellarpay'); ?>
            </th>
            <td>
                <?php echo esc_html($this->subscription->nextBillingAt); ?>
            </td>
        </tr>
        <tr>
            <th>
                <?php esc_html_e('Subscription Status', 'stellarpay'); ?>
            </th>
            <td class="sp2-bg-gray-50 sp2-font-medium sp2-p-4">
                <?php (new StatusLabel($this->subscription))->render(); ?>
            </td>
        </tr>
        <tr>
            <th>
                <?php esc_html_e('Subscription Frequency', 'stellarpay'); ?>
            </th>
            <td>
                <div class="sp-flex sp-items-center">
                    <span>
                        <?php echo wp_kses_post($this->subscription->total); ?>
                    </span>
                    <?php if ($this->subscription->frequency > 1) : ?>
                        <span class="sp-font-normal sp-text-sm sp-ml-1">
                            <?php
                            echo sprintf(
                                // translators: %d number of total payments.
                                esc_html__('(%d total payments)', 'stellarpay'),
                                $this->subscription->frequency // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            );
                            ?>
                    </span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <?php esc_html_e('Payment Method', 'stellarpay'); ?>
            </th>
            <td>
                <?php echo esc_html($this->subscription->paymentMethodTitle); ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Output the product name with a link to the product.
     *
     * @since 1.1.0
     */
    public function renderProductName(\WC_Order_Item_Product $orderItem): void
    {
        $product = $orderItem->get_product();
        $productURL = $product ? $product->get_permalink() : '';

        if (!$product || !$productURL) {
            echo esc_html($orderItem->get_name());
        }

        printf(
            '<a href="%1$s" class="sp-text-blue-600 sp-no-underline" title="%2$s" target="_blank">%3$s</a>',
            esc_url($productURL),
            esc_html__('View product', 'stellarpay'),
            esc_html($orderItem->get_name())
        );
    }
}
