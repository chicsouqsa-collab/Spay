<?php

/**
 * This class is responsible to provide subscription data view for email notification.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails\Views;

use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Factory;
use WC_Order_Item_Product;

/**
 * @since 1.0.0
 */
class AddSubscriptionsDetailsToEmailContent
{
    use SubscriptionUtilities;

    /**
     * @var Subscription[]
     */
    protected array $subscriptions;

    /**
     * @since 1.0.0
     */
    protected WC_Order $order;

    /**
     * @since 1.0.0
     */
    protected bool $plainText;

    /**
     * @since 1.0.0
     * @param WC_Order $order
     */
    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    /**
     * @since 1.0.0
     */
    public function getContent(): string
    {
        return $this->plainText ? $this->getPlainTextContent() : $this->getHTMLContent();
    }

    /**
     * @since 1.0.0
     */
    protected function getPlainTextContent(): string
    {
        $dateFormat = get_option('date_format', 'F j, Y');

        ob_start();

        echo "\n\n";
        esc_html_e('Subscription information', 'stellarpay');
        echo "\n\n";

        foreach ($this->subscriptions as $subscription) {
            $startDate = wp_date($dateFormat, $subscription->createdAt->getTimestamp());
            $name = $this->getLineItemName($subscription->firstOrderItemId);
            $orderItem = new WC_Order_Item_Product($subscription->firstOrderItemId);
            $price = $this->getPriceHtml($orderItem, $subscription);
            $endDate = $subscription->endedAt
                ? $subscription->endedAt->format($dateFormat)
                : esc_html__('Ongoing until canceled', 'stellarpay');

            echo esc_html__('Subscription: ', 'stellarpay');
            echo esc_html($name) . "\n";
            echo esc_html__('Start Date: ', 'stellarpay');
            echo esc_html($startDate) . "\n";
            echo esc_html__('End Date: ', 'stellarpay');
            echo esc_html($endDate) . "\n";
            echo esc_html__('Price: ', 'stellarpay');
            echo esc_html(wp_strip_all_tags($price)) . "\n\n";
        }

        return ob_get_clean();
    }

    /**
     * @since 1.0.0
     */
    protected function getHTMLContent(): string
    {
        $dateFormat = get_option('date_format', 'F j, Y');

        ob_start();
        ?>
        <div style="margin-bottom:40px;">
            <h2><?php
                esc_html_e('Subscription information', 'stellarpay'); ?>
            </h2>
            <table class="td" cellspacing="0" cellpadding="6"
                   style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <thead>
                    <tr>
                        <?php $this->th(esc_html__('Subscription', 'stellarpay')); ?>
                        <?php $this->th(esc_html__('Start Date', 'stellarpay')); ?>
                        <?php $this->th(esc_html__('End Date', 'stellarpay')); ?>
                        <?php $this->th(esc_html__('Price', 'stellarpay')); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($this->subscriptions as $subscription) :
                        $startDate = wp_date(get_option('date_format', 'F j, Y'), $subscription->createdAt->getTimestamp());
                        $orderItem = new WC_Order_Item_Product($subscription->firstOrderItemId);
                        $name = $this->getLineItemName($subscription->firstOrderItemId);
                        $price = $this->getPriceHtml($orderItem, $subscription);
                        $endDate = $subscription->endedAt
                            ? $subscription->endedAt->format($dateFormat)
                            : esc_html__('Ongoing until canceled', 'stellarpay');
                        ?>
                        <tr>
                            <?php $this->td($name);?>
                            <?php $this->td($startDate);?>
                            <?php $this->td($endDate);?>
                            <?php $this->td($price, true);?>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @since 1.0.0
     */
    private function th(string $data): void
    {
        $textAlign = is_rtl() ? 'right' : 'left';

        printf(
            '<th class="td" scope="col" style="text-align:%1$s">%2$s</th>',
            esc_attr($textAlign),
            esc_html($data)
        );
    }

    /**
     * @since 1.0.0
     */
    private function td(string $data, $skipDataEsc = false): void
    {
        $textAlign = is_rtl() ? 'right' : 'left';

        printf(
            '<td class="td" style="text-align:%1$s; vertical-align:middle; border: 1px solid #eee;">%2$s</td>',
            esc_attr($textAlign),
            $skipDataEsc ? $data : esc_html($data) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }


    /**
     * @since 1.0.0
     * @param Subscription[] $subscriptions array of subscriptions
     */
    public function setSubscriptions(array $subscriptions): self
    {
        $this->subscriptions = $subscriptions;

        return $this;
    }

    /**
     * @since 1.0.0
     *
     * @return $this
     */
    public function setPlainText(bool $plainText): self
    {
        $this->plainText =  $plainText;

        return $this;
    }

    /**
     * Get a line item name.
     *
     * @since 1.0.0
     */
    private function getLineItemName(int $itemId): string
    {
        $item = WC_Order_Factory::get_order_item($itemId);
        return $item->get_name();
    }
}
