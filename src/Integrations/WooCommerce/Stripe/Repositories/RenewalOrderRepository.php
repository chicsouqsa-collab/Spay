<?php

/**
 * This class provides logic to query renewal order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.0.0
 */
class RenewalOrderRepository extends OrderRepository
{
    /**
     * @since 1.0.0
     */
    public function getRenewalSubscriptionIdKey(): string
    {
        return dbMetaKeyGenerator('renewal_subscription_id', true);
    }

    /**
     * @since 1.0.0
     */
    public function setRenewalSubscriptionId(WC_Order $order, int $subscriptionId): bool
    {
        $order->update_meta_data($this->getRenewalSubscriptionIdKey(), (string) $subscriptionId);

        return (bool) absint($order->save());
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function getRenewalSubscription(WC_Order $order): ?Subscription
    {
        $subscriptionId = (int) $order->get_meta($this->getRenewalSubscriptionIdKey()) ?: '';

        return $subscriptionId ? Subscription::find($subscriptionId) : null;
    }
}
