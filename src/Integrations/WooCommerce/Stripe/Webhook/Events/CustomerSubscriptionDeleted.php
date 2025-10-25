<?php

/**
 * CustomerSubscriptionDeleted event processor for Stripe.
 *
 * This class is responsible for processing the customer.subscription.deleted event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\SubscriptionEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\SubscriptionEventDTO;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.0.0
 */
class CustomerSubscriptionDeleted extends SubscriptionEventProcessor
{
    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    protected function processSubscription(Subscription $subscription, SubscriptionEventDTO $subscriptionEvent): void
    {
        if (
            ( $subscription->billingTotal <= $subscription->billedCount )
            && $subscription->hasEndDate()
        ) {
            if ($subscription->complete()) {
                $this->addStatusChangeOrderNote($subscription);
            }
        } elseif ($subscription->cancel(true)) {
            $this->addStatusChangeOrderNote($subscription);
        }
    }
}
