<?php

/**
 * This class is responsible to create StellarPay subscriptions for the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\SubscriptionSource;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\Illuminate\Support\Collection;
use WC_Order;
use WC_Order_Item_Product;

/**
 * @since 1.7.0 Rename class.
 * @since 1.0.0
 */
class StellarPaySubscriptionProcessor
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @since 1.5.0 Return bool result.
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function createSubscriptions(WC_Order $order): bool
    {
        $subscriptionOrderItems = $this->getSubscriptionOrderItems($order);

        // Exit if the order does not have subscription type products.
        if ($subscriptionOrderItems->isEmpty()) {
            return false;
        }

        if (! $this->canUpdateSubscriptions($order, $subscriptionOrderItems)) {
            return true;
        }

        foreach ($subscriptionOrderItems as $subscriptionOrderItem) {
            $this->createOrUpdateSubscription($order, $subscriptionOrderItem);
        }

        return true;
    }


    /**
     * @since 1.5.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    private function canUpdateSubscriptions(WC_Order $order, Collection $subscriptionOrderItems): bool
    {
        // Exit if subscription already created.
        if (Subscription::findByFirstOrderId($order->get_id())) {
            $canUpdateSubscriptions = false;

            // WooCommerce edit order items when the cart updates. It is possible that we have a subscription for outdated order and order item.
            // We should delete the subscription if the order item is not present to avoid abandoning the subscription.
            $subscriptions = Subscription::findAllByFirstOrderId($order->get_id());
            foreach ($subscriptions as $subscription) {
                if ($order->get_item($subscription->firstOrderItemId) instanceof \WC_Order_Item) {
                    continue;
                }

                $subscription->delete();
                $canUpdateSubscriptions = true;
            }


            // It is possible that we do not have a subscription for the order item.
            // We should create a subscription for the order item.
            foreach ($subscriptionOrderItems as $subscriptionOrderItem) {
                if (! Subscription::findByFirstOrderAndItemId($order->get_id(), $subscriptionOrderItem->get_id())) {
                    $canUpdateSubscriptions = true;

                    // We are inside this condition, this means that the order is updated,
                    //and we should add subscripton for missing order items and update existing.
                    break;
                }
            }

            return $canUpdateSubscriptions;
        }

        return true;
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    protected function createOrUpdateSubscription(WC_Order $order, WC_Order_Item_Product $orderItem): void
    {
        $subscription = Subscription::findByFirstOrderAndItemId($order->get_id(), $orderItem->get_id());

        if ($subscription) {
            $this->updateSubscription($subscription, $order, $orderItem);

            return;
        }

        $this->createSubscription($order, $orderItem);
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException|Exception
     */
    protected function createSubscription(WC_Order $order, WC_Order_Item_Product $orderItem): void
    {
        $subscription = Subscription::create($this->getSubscriptionData($order, $orderItem));

        $subscription->save();
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    protected function updateSubscription(
        Subscription $subscription,
        WC_Order $order,
        WC_Order_Item_Product $orderItem
    ): void {
        $subscription->fill($this->getSubscriptionData($order, $orderItem));

        // Skip if the subscription is clean and the stripe subscription is already created.
        if ($subscription->transactionId && $subscription->isClean()) {
            return;
        }

        $subscription->save();
    }

    /**
     * @since 1.8.0 Throw exception if the product is not the StellarPay subscription product.
     *              Add initial amount, recurring amount and currency code.
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    private function getSubscriptionData(WC_Order $order, WC_Order_Item_Product $orderItem): array
    {
        $subscriptionProduct = ProductFactory::makeFromProduct($orderItem->get_product());

        if (! $subscriptionProduct instanceof SubscriptionProduct) {
            throw new Exception('Invalid subscription product.');
        }

        $initialAmount = new Money((float)$orderItem->get_subtotal('edit'), $order->get_currency());
        $recurringAmount = $initialAmount;

        if ($subscriptionProduct->isOnSale()) {
            $recurringAmount = new Money(
                (float)$subscriptionProduct->getRegularAmount('edit') * $orderItem->get_quantity('edit'),
                $order->get_currency()
            );
        }

        $data = [
            'status' => SubscriptionStatus::PENDING(),
            'customerId' => $order->get_user_id(),
            'firstOrderId' => $order->get_id(),
            'firstOrderItemId' => $orderItem->get_id(),
            'initialAmount' => $initialAmount,
            'recurringAmount' => $recurringAmount,
            'currencyCode' => $order->get_currency(),
            'period' => $subscriptionProduct->getPeriod(),
            'frequency' => $subscriptionProduct->getFrequency(),
            'paymentGatewayMode' => $this->orderRepository->getPaymentGatewayMode($order),
            'source' => SubscriptionSource::WOOCOMMERCE()
        ];

        if ($subscriptionProduct->getProductType()->isInstallmentPayments()) {
            $data['billingTotal'] = $subscriptionProduct->getNumberOfPayments('edit');
        }

        return $data;
    }
}
