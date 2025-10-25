<?php

/**
 * This class is responsible for render the subscription status label.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates;

use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\MySubscriptionDTO;
use StellarPay\Core\Contracts\View;

/**
 * @since 1.1.0
 */
class StatusLabel extends View
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
     * Output the status label
     *
     * @since 1.1.0
     */
    public function getHTML(): string
    {
        switch ($this->subscription->statusColor) {
            case 'green':
                $statusColorClass = 'sp-bg-green-500';
                break;

            case 'orange':
                $statusColorClass = 'sp-bg-orange-500';
                break;

            default:
                $statusColorClass = 'sp-bg-gray-500';
                break;
        }

        $isPendingOnOrderReceivedPage = is_order_received_page() && $this->subscription->status->isPending();

        $label = $isPendingOnOrderReceivedPage
            ? esc_html__('Checking status', 'stellarpay')
            : $this->subscription->statusLabel;

        ob_start();
        ?>
        <span
            class="stellarpay-subscriptions__status-badge sp-inline-flex sp-items-center sp-border-solid sp-rounded-md sp-border sp-py-0.5 sp-px-2 sp-font-medium sp-leading-relaxed sp-text-xs <?php echo esc_attr($this->subscription->statusBadgeClasses); ?>"
        >
            <?php if ($isPendingOnOrderReceivedPage) : ?>
                <img
                    src="<?php echo esc_url(get_admin_url() . 'images/loading.gif'); ?>"
                    class="stellarpay-subscriptions__status-badge--is-loading sp-mr-1"
                    data-subscription-id="<?php echo esc_attr((string) $this->subscription->id); ?>"
                    data-order-id="<?php echo esc_attr((string) $this->subscription->order->get_id()); ?>"
                />
            <?php endif; ?>

            <?php if (! $isPendingOnOrderReceivedPage) : ?>
                <span class="<?php echo esc_attr("sp-h-3 sp-w-3 {$statusColorClass} sp-inline-block sp-rounded-full sp-mr-1"); ?>"></span>
            <?php endif; ?>

            <?php echo esc_html($label); ?>
        </span>
        <?php
        return ob_get_clean();
    }
}
