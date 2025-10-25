<?php

/**
 * This class is responsible to provide the price data for the Stripe rest api request.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Strategies
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\SubscriptionRepository;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Variation;

/**
 * Class PriceDataStrategy
 *
 * @since 1.8.0 Add support for product variation
 * @since 1.0.0
 */
class PriceDataStrategy implements DataStrategy
{
    /**
     * @since 1.0.0
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @since 1.0.0
     */
    protected ?WC_Order $order;

    /**
     * @since 1.8.0
     */
    protected WC_Product $product;

    /**
     * @since 1.8.0
     */
    protected ?WC_Product_Variation $productVariation = null;

    /**
     * @since 1.0.0
     */
    private ?Subscription $subscription;

    /**
     * @since 1.0.0
     */
    private ?WC_Order_Item_Product $orderItem;

    /**
     * @since 1.0.0
     */
    private ?string $stripeProductId;

    /**
     * @since 1.0.0
     */
    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * @since 1.0.0
     */
    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setOrder(WC_Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setOrderItem(WC_Order_Item_Product $item): self
    {
        $this->orderItem = $item;

        return $this;
    }

    /**
     * @since 1.8.0
     */
    public function setProduct(WC_Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @since 1.8.0
     */
    public function setProductVariation(WC_Product_Variation $productVariation): self
    {
        $this->productVariation = $productVariation;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setStripeProductId(string $stripeProductId): self
    {
        $this->stripeProductId = $stripeProductId;

        return $this;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function generateData(): array
    {
        if (! $this->subscription || ! $this->order || ! $this->orderItem || ! $this->stripeProductId) {
            throw new Exception('Subscription, order, item and stripe product id must be set before generating data.');
        }

        $orderCurrency = $this->order->get_currency();

        $unitAmount = Money::make(
            $this->getPriceIncludingTax() / $this->orderItem->get_quantity(),
            $orderCurrency
        );

        $data = [
            'currency' => $orderCurrency,
            'product' => $this->stripeProductId,
            'unit_amount_decimal' => $unitAmount->getMinorAmount(),
        ];

        $interval = $this->subscription->period->getValue();
        $intervalCount = $this->subscription->frequency;

        if ($interval && $intervalCount) {
            $data['recurring'] = [
                'interval' => $interval,
                'interval_count' => $intervalCount,
            ];
        }

        $data['metadata'] = [
            'product_id' => $this->product->get_id(),
            'product_url' => $this->productVariation instanceof WC_Product_Variation
                ? esc_url_raw($this->productVariation->get_permalink())
                : esc_url_raw($this->product->get_permalink()),
            'site_url' => esc_url(get_site_url()),
        ];

        if ($this->productVariation instanceof WC_Product_Variation) {
            $data['metadata']['variation_id'] = $this->productVariation->get_id();
        }

        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the price data.
         *
         * @since 1.0.0
         *
         * @param array $data
         * @param Subscription $subscription
         * @param WC_Order $order
         * @param WC_Order_Item $item
         * @param string $stripeProductId
         * @param WC_Product $product
         * @param WC_Product_Variation $productVariation
         */
        return apply_filters(
            'stellarpay_wc_stripe_generate_price_data',
            $data,
            $this->subscription,
            $this->order,
            $this->orderItem,
            $this->stripeProductId,
            $this->product,
            $this->productVariation
        );
    }

    /**
     * @since 1.8.0
     */
    private function getPriceIncludingTax(): float
    {
        return (float)wc_get_price_including_tax(
            $this->productVariation ?? $this->product,
            ['price' => $this->subscription->recurringAmount->getAmount()]
        );
    }
}
