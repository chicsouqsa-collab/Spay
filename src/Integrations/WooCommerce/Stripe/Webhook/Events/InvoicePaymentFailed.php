<?php

/**
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\SubscriptionInvoiceEventProcessor;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\InvoiceEventDTO;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.3.0
 */
class InvoicePaymentFailed extends SubscriptionInvoiceEventProcessor
{
    /**
     * @since 1.3.0
     * @throws BindingResolutionException|Exception
     */
    protected function processSubscriptionInvoice(Subscription $subscription, InvoiceEventDTO $invoiceEventDTO): void
    {
        $statusPastDue = SubscriptionStatus::PAST_DUE();

        if (! $statusPastDue->equals($subscription->status)) {
            // We use resume date as next billing date if subscription is paused.
            // If subscription payment fails when resumed, we will set the next billing date to the resumed date.
            // Otherwise, the next billing date will remain our date.
            if ($subscription->resumedAt) {
                $subscription->nextBillingAt = $subscription->resumedAt;
                $subscription->nextBillingAtGmt = $subscription->resumedAtGmt;
            }

            $subscription->updateStatus($statusPastDue);
            OrderNote::onSubscriptionStatusChange($subscription, ModifierContextType::WEBHOOK());
        }
    }
}
