<?php

/**
 * This class is responsible to generate email notification for the admin when subscription status changes.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Emails\Views\SubscriptionStatusChangedAdminEmailContent;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\Arrays\Arr;
use WC_Email;
use WC_Order;

/**
 * @since 1.0.0
 */
class SubscriptionStatusChangedAdminEmail extends WC_Email
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    private Subscription $subscription;

    /**
     * @since 1.0.0
     */
    private WC_Order $order;

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->id = 'sp_subscription_status_changed_admin';
        $this->title = esc_html__('Subscription Changed', 'stellarpay');
        $this->description = esc_html__('Subscription Changed emails are sent when customer switches the status of subscription.', 'stellarpay');
        $this->customer_email = false;
        $this->heading = esc_html__('Subscription Changed.', 'stellarpay');
        $this->subject = esc_html__('[{site_name}] Subscription status has changed to {subscription_status} for order #{order_number}', 'stellarpay');

        // Placeholders.
        $this->placeholders['{subscription_status}'] = '';
        $this->placeholders['{order_number}'] = '';
        $this->placeholders['{site_name}'] = $this->get_blogname();

        parent::__construct();
        $this->recipient = $this->get_option('recipient');
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        parent::init_form_fields();

        $defaultEmail = get_bloginfo('admin_email');

        $recipientField = [
            'recipient' => [
                'title'       => esc_html__('Recipient(s)', 'stellarpay'),
                'type'        => 'text',
                'description' => sprintf(
                    // translators: 1: WP admin email
                    esc_html__('Enter recipients (comma separated) for this email. Defaults to %s.', 'stellarpay'),
                    '<code>' . esc_html($defaultEmail) . '</code>'
                ),
                'placeholder' => '',
                'default'     => $defaultEmail,
                'desc_tip'    => true,
            ]
        ];

        $this->form_fields = Arr::insert_after_key('enabled', $this->form_fields, $recipientField);
    }

    /**
     * @since 1.0.0
     */
    public function get_default_subject(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->subject;
    }

    /**
     * @since 1.0.0
     */
    public function get_default_heading(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->heading;
    }

    /**
     * @since 1.0.0
     */
    public function get_default_additional_content(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return '';
    }

    /**
     * @since 1.0.0
     */
    public function triggerEmailForSubscription(Subscription $subscription): void
    {
        if (!$subscription->firstOrderId) {
            return;
        }

        $this->subscription = $subscription;
        $this->order = wc_get_order($subscription->firstOrderId);

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        $this->placeholders['{subscription_status}'] = $subscription->getFormattedStatusLabel();
        $this->placeholders['{order_number}'] = $this->order->get_order_number();

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function trigger(int $orderId, WC_Order $order = null): void
    {
        throw new Exception('This method is not implemented.');
    }

    /**
     * @since 1.0.0
     */
    public function get_content_html(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $statusChangedAdminEmailContent = new SubscriptionStatusChangedAdminEmailContent($this->order, $this->subscription);
        return $statusChangedAdminEmailContent
            ->setEmail($this)
            ->setPlainText(false)
            ->setIsAdminEmail(true)
            ->setEmailHeading($this->get_heading())
            ->setAdditionalContent($this->get_additional_content())
            ->setStoreName($this->get_blogname())
            ->getContent();
    }

    /**
     * @since 1.0.0
     */
    public function get_content_plain(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $statusChangedAdminEmailContent = new SubscriptionStatusChangedAdminEmailContent($this->order, $this->subscription);
        return $statusChangedAdminEmailContent
            ->setEmail($this)
            ->setPlainText(true)
            ->setIsAdminEmail(true)
            ->setEmailHeading($this->get_heading())
            ->setAdditionalContent($this->get_additional_content())
            ->setStoreName($this->get_blogname())
            ->getContent();
    }
}
