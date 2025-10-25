<?php

/**
 * This class is used to copy StellarPay order metadata to subscription metadata.
 * It is used to process renewal requests for subscriptions.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;

/**
 * @since 1.7.0
 */
class CopyStellarPayOrderMetadataToSubscriptionMetadata
{
    /**
     * @since 1.7.0
     */
    public function __invoke(PaymentContext $paymentContext)
    {
        $paymentMethodId = $paymentContext->payment_method; // @phpstan-ignore-line

        // If the payment method is not ours, then return.
        if (Constants::GATEWAY_ID !== $paymentMethodId) {
            return;
        }

        $order = $paymentContext->order; // @phpstan-ignore-line
        $subscriptions = wcs_get_subscriptions_for_order($order);

        // Early return if there are no subscriptions.
        if (! $subscriptions) {
            return;
        }

        // Get the order metadata.
        $orderMetadata = array_filter($order->get_meta_data(), function ($meta) {
            return false !== strpos($meta->key, \StellarPay\Core\Constants::PLUGIN_SLUG);
        });

        // Copy the order metadata to each subscription.
        foreach ($subscriptions as $subscription) {
            foreach ($orderMetadata as $meta) {
                $subscription->update_meta_data($meta->key, $meta->value);
            }
            $subscription->save();
        }
    }
}
