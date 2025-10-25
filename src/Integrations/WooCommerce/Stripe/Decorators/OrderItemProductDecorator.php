<?php

/**
 * This class is responsible to provide logic to perform on the WooCommerce order item product.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Decorators
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Decorators;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item_Product;

/**
 * @since 1.0.0
 */
class OrderItemProductDecorator
{
    /**
     * @since 1.0.0
     */
    protected WC_Order_Item_Product $orderItemProduct;

    /**
     * @since 1.0.0
     */
    protected WC_Order $order;

    /**
     * @since 1.8.0
     * @var Subscription|null
     */
    protected ?Subscription $subscription;

    /**
     * @since 1.8.0 Add subscription property.
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function __construct(WC_Order_Item_Product $orderItemProduct, WC_Order $order = null)
    {
        $this->orderItemProduct = $orderItemProduct;
        $this->order = $order ?? $this->orderItemProduct->get_order();
        $this->subscription = Subscription::findByFirstOrderAndItemId($this->order->get_id(), $this->orderItemProduct->get_id());

        if (! $this->subscription instanceof Subscription) {
            throw new Exception('Invalid subscription.');
        }
    }

    /**
     * @since 1.8.0 Update logic to get recurring amount from subscription.
     * @since 1.0.0
     */
    public function getSubscriptionAmount(): Money
    {
        // @todo we should remove this logic after adding a database migration (after few stable releases) to add initial amount, recurring amount and currency code to subscription.
        if (! $this->subscription->recurringAmount) {
            $subscriptionAmount = (float) $this->orderItemProduct->get_subtotal() + (float) $this->orderItemProduct->get_subtotal_tax();

            return Money::make($subscriptionAmount, $this->order->get_currency());
        }

        $subscriptionAmountIncludingTax =  (float)wc_get_price_including_tax(
            $this->orderItemProduct->get_product(),
            ['price' => $this->subscription->recurringAmount->getAmount()]
        );

        return Money::make(
            $subscriptionAmountIncludingTax,
            $this->subscription->currencyCode
        );
    }

    /**
     * @since 1.0.0
     */
    public function getSubscriptionUnitAmount(): Money
    {
        $subscriptionAmount = $this->getSubscriptionAmount();

        return Money::make(
            $subscriptionAmount->getAmount() / $this->orderItemProduct->get_quantity(),
            $this->order->get_currency()
        );
    }
}
