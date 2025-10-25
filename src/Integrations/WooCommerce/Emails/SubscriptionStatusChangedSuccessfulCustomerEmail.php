<?php

/**
 * This class is responsible to generate email notification for the customer when subscription status changes.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Emails\Views\SubscriptionStatusChangedSuccessfulCustomerEmailContent;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Email;
use WC_Order;

/**
 * @since 1.0.0
 */
class SubscriptionStatusChangedSuccessfulCustomerEmail extends WC_Email
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
        $this->id = 'sp_subscription_status_changed_successful';
        $this->title = esc_html__('Subscription Change Successful', 'stellarpay');
        $this->description = esc_html__(
            'This email is sent to customers when their subscription status changes.',
            'stellarpay'
        );
        $this->heading = esc_html__('Your subscription status has changed.', 'stellarpay');
        $this->subject = esc_html__('Your {site_title} subscription status has changed to {subscription_status}', 'stellarpay');
        $this->placeholders['{subscription_status}'] = '';

        $this->customer_email = true;

        parent::__construct();
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
    public function triggerEmailForSubscription(Subscription $subscription): void
    {
        if (! is_a($subscription, Subscription::class)) {
            return;
        }

        if (! $subscription->firstOrderId) {
            return;
        }

        // @todo Test if it reflects the correct status in the initial Email?
        $this->subscription = $subscription;
        $this->order = wc_get_order($subscription->firstOrderId);

        $this->recipient = $this->order->get_billing_email();

        if (! $this->is_enabled() || ! $this->get_recipient()) {
            return;
        }

        $this->placeholders['{subscription_status}'] = $subscription->getFormattedStatusLabel();

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
     */
    public function get_content_html(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $statusChangedSuccessfulCustomerEmailContent = new SubscriptionStatusChangedSuccessfulCustomerEmailContent(
            $this->order,
            $this->subscription
        );

        return $statusChangedSuccessfulCustomerEmailContent
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
        $statusChangedSuccessfulCustomerEmailContent = new SubscriptionStatusChangedSuccessfulCustomerEmailContent(
            $this->order,
            $this->subscription
        );

        return $statusChangedSuccessfulCustomerEmailContent
            ->setEmail($this)
            ->setPlainText(true)
            ->setIsAdminEmail(true)
            ->setEmailHeading($this->get_heading())
            ->setAdditionalContent($this->get_additional_content())
            ->setStoreName($this->get_blogname())
            ->getContent();
    }
}
