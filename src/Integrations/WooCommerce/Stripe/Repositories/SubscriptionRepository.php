<?php

/**
 * Subscription repository.
 *
 * This class is responsible for handling subscription data.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;

/**
 * Class SubscriptionRepository
 *
 * @since 1.0.0
 */
class SubscriptionRepository
{
    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @since 1.0.0
     */
    public function getOrder(Subscription $subscription): ?WC_Order
    {
        $order = wc_get_order($subscription->firstOrderId);

        return $order instanceof WC_Order ? $order : null;
    }

    /**
     * @since 1.0.0
     */
    public function getCustomerId(Subscription $subscription): ?string
    {
        return $this->orderRepository->getCustomerId(wc_get_order($subscription->firstOrderId));
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentMethodId(Subscription $subscription): ?string
    {
        return $this->orderRepository->getPaymentMethodId(wc_get_order($subscription->firstOrderId));
    }
}
