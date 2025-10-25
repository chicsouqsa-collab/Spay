<?php

/**
 * This class is responsible to provide content for subscription changed admin email notification.
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
class SubscriptionStatusChangedAdminEmailContent extends EmailView
{
    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function getPlainTextContent(): string
    {
        ob_start();

        echo esc_html($this->emailHeading) . "\n\n";

        echo $this->getIntroductionContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo "\n\n----------------------------------------\n\n";

        $this->doActionWoocommerceEmailOrderMeta();

        echo "\n\n----------------------------------------\n\n";

        /*
         * @hooked WC_Emails::customer_details() Shows customer details
         * @hooked WC_Emails::email_address() Shows email address
         */
        do_action('woocommerce_email_customer_details', $this->order, $this->isAdminEmail, $this->plainText, $this->email);

        echo "\n\n----------------------------------------\n\n";

        /**
         * Show user-defined additional content - this is set in each email's settings.
         */
        if ($this->additionalContent) {
            echo esc_html(wp_strip_all_tags(wptexturize($this->additionalContent)));
            echo "\n\n----------------------------------------\n\n";
        }

        echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));


        return ob_get_clean();
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function getHTMLContent(): string
    {
        ob_start();

        do_action('woocommerce_email_header', $this->emailHeading, $this->email);

        echo $this->getIntroductionContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        echo '<br/><br/>';

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
        * 1. Customer name
        * 2. New subscription status.
        */
            wp_kses_post(esc_html__('The subscription status for customer %1$s has been updated to %2$s. Please find the updated subscription details in the table below.', 'stellarpay')),
            $this->order->get_formatted_billing_full_name(),
            $this->subscription->status->label()
        );
    }
}
