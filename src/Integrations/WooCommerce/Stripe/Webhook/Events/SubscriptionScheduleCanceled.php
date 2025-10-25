<?php

/**
 * SubscriptionScheduleCanceled event processor for Stripe.
 *
 * This class is responsible for processing the subscription_schedule.canceled event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\SubscriptionEventProcessor;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\SubscriptionEventDTO;

/**
 * @since 1.1.0 Make compatible with update EventProcessor class.
 * @since 1.0.0
 */
class SubscriptionScheduleCanceled extends SubscriptionEventProcessor
{
    /**
     * @since 1.0.0
     * @since 1.4.0 - Updated to processSubscription method.
     *
     * @throws BindingResolutionException
     * @throws Exception
    */
    protected function processSubscription(Subscription $subscription, SubscriptionEventDTO $subscriptionEvent): void
    {
        $cancelAtPeriodEnd = $this->getEventDTO()->getValueFromMetadata('cancelAtPeriodEnd');

        if ($cancelAtPeriodEnd) {
            $subscription->cancelAtPeriodEnd();
        } elseif ($subscription->cancel()) {
            $this->addStatusChangeOrderNote($subscription);
        }
    }
}
