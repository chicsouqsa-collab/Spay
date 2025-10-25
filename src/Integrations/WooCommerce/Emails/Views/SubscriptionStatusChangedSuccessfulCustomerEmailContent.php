<?php

/**
 * This class is responsible to provide content for subscription changed customer email notification.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails\Views;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.0.0
 */
class SubscriptionStatusChangedSuccessfulCustomerEmailContent extends EmailView
{
    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws Exception
     */
    protected function getPlainTextContent(): string
    {
        ob_start();

        echo esc_html($this->emailHeading) . "\n\n";

        /* translators: %s: Customer first name */
        echo sprintf(esc_html__('Hi %s,', 'stellarpay'), esc_html($this->order->get_billing_first_name())) . "\n\n";
        echo $this->getIntroductionContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $this->doActionWoocommerceEmailOrderMeta();

        echo "\n\n----------------------------------------\n\n";

        do_action('woocommerce_email_customer_details', $this->order, $this->isAdminEmail, $this->plainText, $this->email);

        echo "\n\n----------------------------------------\n\n";

        /**
         * Show user-defined additional content - this is set in each email's settings.
         */
        if ($this->additionalContent) {
            echo esc_html(wp_strip_all_tags(wptexturize($this->additionalContent)));
            echo "\n\n----------------------------------------\n\n";
        }

        echo esc_html(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));


        return ob_get_clean();
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws Exception
     */
    protected function getHTMLContent(): string
    {
        ob_start();

        do_action('woocommerce_email_header', $this->emailHeading, $this->email); ?>

        <?php /* translators: %s: Customer first name */ ?>
        <p><?php printf(esc_html__('Hi %s,', 'stellarpay'), esc_html($this->order->get_billing_first_name())); ?></p>
        <p><?php echo $this->getIntroductionContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

        <?php

        $this->doActionWoocommerceEmailOrderMeta();
        do_action('woocommerce_email_customer_details', $this->order, $this->isAdminEmail, $this->plainText, $this->email);

        /**
         * Show user-defined additional content - this is set in each email's settings.
         */
        if ($this->additionalContent) {
            echo wp_kses_post(wpautop(wptexturize($this->additionalContent)));
        }

        do_action('woocommerce_email_footer', $this->email);


        return ob_get_clean();
    }

    /**
     * @since 1.4.0
     * @throws Exception
     */
    private function getIntroductionContent(): string
    {
        return sprintf(
        /*
        * Translators:
        * 1. New Subscription status.
        * 2. Store name.
        * 3. Manage subscription link.
        */
            wp_kses_post(__('We wanted to let you know that your subscription status has changed to %1$s. To manage your subscription for %2$s <a href="%3$s">click here</a>.', 'stellarpay')),
            esc_html($this->subscription->status->label()),
            esc_html($this->storeName),
            esc_url(wc_get_account_endpoint_url("my-subscriptions/{$this->subscription->id}"))
        );
    }
}
