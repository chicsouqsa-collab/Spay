<?php

/**
 * This class is responsible to add subscription details to the WooCommerce order related emails.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails\EmailCustomization
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails\EmailCustomization;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Emails\Views\AddSubscriptionsDetailsToEmailContent;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Email;
use WC_Order;

/**
 * @since 1.0.0
 */
class AddSubscriptionsDetailsToEmail
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(WC_Order $order, bool $isAdminEmail, bool $plainText, WC_Email $email, Subscription $subscription = null): void
    {
        $subscriptions = [];

        if ($subscription instanceof Subscription) {
            $subscriptions = [$subscription];
        } elseif ($subscriptionsForOrder = $this->getSubscriptionsForOrder($order)) {
            $subscriptions = $subscriptionsForOrder;
        }

        if (! empty($subscriptions)) {
            $addSubscriptionsDetailsToEmailContent = new AddSubscriptionsDetailsToEmailContent($order);

            echo $addSubscriptionsDetailsToEmailContent // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ->setPlainText($plainText)
                ->setSubscriptions($subscriptions)
                ->getContent();
        }
    }
}
