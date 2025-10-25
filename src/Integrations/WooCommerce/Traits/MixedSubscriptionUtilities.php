<?php

/**
 * This class is responsible for providing utilities for mixed subscription products.
 *
 * @package StellarPay\Integrations\WooCommerce\Traits
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\WooSubscriptionUtilities;
use StellarPay\PluginSetup\Environment;
use WC_Order;
use WC_Product;
use WC_Subscriptions_Product;

/**
 * @since 1.8.0 Add "hasOneOfSubscriptionTypeInTheOrder" function.
 * @since 1.7.0
 */
trait MixedSubscriptionUtilities
{
    use SubscriptionUtilities;
    use WooSubscriptionUtilities;

    /**
     * @since 1.7.0
     */
    protected function hasMixedSubscriptionsInTheCart(): bool
    {
        return $this->cartContainsSubscription() && $this->cartContainsWooSubscription();
    }

    /**
     * @since 1.7.0
     */
    public function hasAnotherSubscriptionTypeInTheCart(WC_Product $product): bool
    {
        $isWooSubscription = WC_Subscriptions_Product::is_subscription($product);
        $isStellarPaySubscription = $this->isSubscriptionProduct($product);

        if (! $isWooSubscription && ! $isStellarPaySubscription) {
            return false;
        }

        if ($isWooSubscription && $this->cartContainsSubscription()) {
            return true;
        }

        if ($isStellarPaySubscription && $this->cartContainsWooSubscription()) {
            return true;
        }

        return false;
    }

    /**
     * @since 1.8.0
     *
     * @throws BindingResolutionException
     */
    public function hasOneOfSubscriptionTypeInTheOrder(WC_Order $order): bool
    {
        return $this->hasSubscriptionProduct($order)
               || (Environment::isWooSubscriptionActive() && $this->hasWooSubscriptionProduct($order));
    }
}
