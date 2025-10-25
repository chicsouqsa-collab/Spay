<?php

/**
 * This class is responsible to trigger subscription status changed emails.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails\Actions;

use StellarPay\Integrations\WooCommerce\Emails\SubscriptionStatusChangedAdminEmail;
use StellarPay\Integrations\WooCommerce\Emails\SubscriptionStatusChangedSuccessfulCustomerEmail;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Emails;

/**
 * @since 1.0.0
 */
class SentSubscriptionStatusChangedEmails
{
    /**
     * @since 1.0.0
     */
    public function __invoke(Subscription $subscription): void
    {
        // We send subscription status-changed email notification only after order payment completed.
        $parentOrder = wc_get_order($subscription->firstOrderId);

        if ($parentOrder instanceof \WC_Order && in_array($parentOrder->get_status(), wc_get_is_paid_statuses(), true)) {
            // Load WooCommerce emails.
            WC_Emails::instance();

            $emailStatusChangedAdminEmail = new SubscriptionStatusChangedAdminEmail();
            $emailStatusChangedSuccessfulCustomerEmail = new SubscriptionStatusChangedSuccessfulCustomerEmail();

            $emailStatusChangedAdminEmail->triggerEmailForSubscription($subscription);
            $emailStatusChangedSuccessfulCustomerEmail->triggerEmailForSubscription($subscription);
        }
    }
}
