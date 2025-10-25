<?php

/**
 * This class is responsible to generate data from the Woocommerce order and order item to create subscription schedule on the Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Strategies
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\SubscriptionRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Services\PriceService;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

use function StellarPay\Core\sanitizeTextField;

/**
 * @since 1.0.0
 */
class SubscriptionScheduleDataStrategy implements DataStrategy
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     * @var SubscriptionRepository
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @since 1.0.0
     */
    protected ?WC_Order $order;

    /**
     * @since 1.0.0
     */
    protected WC_Order_Item_Product $orderItem;

    /**
     * @since 1.0.0
     */
    protected PriceService $priceService;

    /**
     * @since 1.0.0
     */
    protected Subscription $subscription;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository, SubscriptionRepository $subscriptionRepository, PriceService $priceService)
    {
        $this->orderRepository = $orderRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->priceService = $priceService;
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
     * Set the WooCommerce subscription.
     *
     * @since 1.0.0
     */
    public function setOrder(WC_Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Set the WooCommerce subscription.
     *
     * @since 1.0.0
     */
    public function setOrderItem(WC_Order_Item_Product $orderItem): self
    {
        $this->orderItem = $orderItem;

        return $this;
    }

    /**
     * This function generates data for the Stripe new subscription rest api query.
     *
     * @todo: add support for fees, taxes, shipping, onetime payment, trials and coupons.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function generateData(): array
    {
        $data = [
            'customer' => $this->orderRepository->getCustomerId($this->order),
            'default_settings' => [
                'default_payment_method' => $this->orderRepository->getPaymentMethodId($this->order),

            ],
            // Start the Stripe subscription from the next billing date.
            'start_date' => strtotime(Temporal::getFormattedDateTime($this->subscription->nextBillingAtGmt)),
            'end_behavior' => 'release',
            'phases' => $this->generatePhases()
        ];

        if ($this->subscription->billingTotal) {
            // Subscription should be canceled when the subscription schedule ends.
            $data['end_behavior'] = 'cancel';
        }


        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the new subscription schedule data.
         *
         * @since 1.9.0
         *
         * @param array $data
         * @param Subscription $subscription
         * @param WC_Order $order
         * @param WC_Order_Item $orderItem
         */
        return apply_filters(
            'stellarpay_wc_stripe_generate_new_subscription_schedule_data',
            $data,
            $this->subscription,
            $this->order,
            $this->orderItem
        );
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    protected function generatePhases(): array
    {
        $firstName  = sanitizeTextField($this->order->get_billing_first_name());
        $lastName = sanitizeTextField($this->order->get_billing_last_name());
        $billingName = trim("$firstName $lastName");

        $phase =  [
            'items' => [
                [
                    'quantity' => $this->orderItem->get_quantity(),
                    'price' => $this->getItemPriceId(),
                ]
            ],
            'proration_behavior' => 'none',
        ];

        if ($this->subscription->billingTotal) {
            // Reduce iteration by one because customer already paid first invoice when checkout.
            $phase['iterations'] = $this->subscription->billingTotal - 1;
        }

        $phase['metadata'] = [
            'customer_name' => $billingName,
            'customer_email' => sanitize_email($this->order->get_billing_email()),
            'order_id' => $this->order->get_id(),
            'order_item_id' => $this->orderItem->get_id(),
            'subscription_id' => $this->subscription->id,
            'subscription_source' => $this->subscription->source->getValue(),
            'site_url' => esc_url(get_site_url())
        ];

        return [$phase];
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function getItemPriceId(): string
    {
        return $this->priceService->create($this->subscription, $this->order, $this->orderItem);
    }
}
