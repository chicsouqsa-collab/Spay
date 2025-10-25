<?php

/**
 * SubscriptionUtilities
 *
 * This trait is responsible for providing utilities for subscription related operations.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Hooks;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Core\ValueObjects\Money;
use StellarPay\Core\ValueObjects\RefundType;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariationRepository;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionService;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\Illuminate\Support\Collection;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use StellarPay\Integrations\WooCommerce\Stripe\Decorators\OrderItemProductDecorator;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\RefundDTO;
use StellarPay\PaymentGateways\Stripe\Services\InvoiceService;
use StellarPay\PaymentGateways\Stripe\Services\RefundService;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\RefundDTO as StripeResponseRefundDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\RefundEventDTO;
use StellarPay\PaymentGateways\Stripe\Services\ChargeService;
use StellarPay\Subscriptions\DataTransferObjects\CartItemDTO;
use WC_Product_Variable;
use WC_Product_Variation;

use function StellarPay\Core\container;

/**
 * Trait SubscriptionUtilities
 *
 * @since 1.0.0
 */
trait SubscriptionUtilities
{
    /**
     * Check if the order is a subscription.
     *
     * Note: use this after customer checkout, during checkout use "hasSubscriptionProduct".
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function isSubscription(WC_Order $order): bool
    {
        $subscription = Subscription::findByFirstOrderId($order->get_id());

        return $subscription instanceof Subscription;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function isNotSubscription(WC_Order $order): bool
    {
        return ! $this->isSubscription($order);
    }

    /**
     * @since 1.0.0
     *
     * @return array<Subscription>|null
     *
     * @throws BindingResolutionException
     */
    public function getSubscriptionsForOrder(WC_Order $order): ?array
    {
        return Subscription::findAllByFirstOrderId($order->get_id());
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function hasSubscriptionProduct(WC_Order $order): bool
    {
        return $this->getSubscriptionOrderItems($order)->isNotEmpty();
    }

    /**
     * @since 1.8.0 Add support for product variation
     * @since 1.0.0
     * @return Collection<WC_Order_Item_Product>
     * @throws BindingResolutionException
     */
    public function getSubscriptionOrderItems(WC_Order $order): Collection
    {
        $collection = Collection::make();

        $orderItems = $order->get_items();

        foreach ($orderItems as $orderItem) {
            if (! $orderItem instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $orderItem->get_product();

            if ($this->isSubscriptionProduct($product)) {
                $productType = ProductFactory::makeFromProduct($product);

                if ($productType instanceof SubscriptionProduct) {
                    $collection->add($orderItem);
                }
            }
        }

        return $collection;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     */
    protected function cancelStripeSubscription(Subscription $subscription): ?bool
    {
        if (! $subscription->transactionId) {
            return null;
        }

        if ($subscription->isScheduleType()) {
            $subscriptionScheduleDTO = container(SubscriptionScheduleService::class)
                ->cancel($subscription->transactionId);
            $isCanceled = $subscriptionScheduleDTO->isCanceled();
        } else {
            $subscriptionDTO = container(SubscriptionService::class)
                ->cancelSubscription($subscription->transactionId);
            $isCanceled = $subscriptionDTO->isCanceled();
        }

        if (! $isCanceled) {
            throw new Exception('Subscription is not canceled');
        }

        return true;
    }

    /**
     * @since 1.3.0
     *
     * @throws Exception|BindingResolutionException
     */
    protected function cancelAtPeriodEndStripeSubscription(Subscription $subscription): ?bool
    {
        if (! $subscription->transactionId) {
            return null;
        }

        /**
         * Fires just before scheduling to cancel at the period end a subscription.
         *
         * @since 1.8.0
         * @hook stellarpay_subscription_scheduling_to_cancel_at_period_end
         * @param Subscription $subscription The subscription to be canceled at the end of the current period.
         */
        Hooks::doAction('stellarpay_subscription_scheduling_to_cancel_at_period_end', $subscription);

        if ($subscription->isScheduleType()) {
            $subscriptionScheduleDTO = container(SubscriptionScheduleService::class)
                ->cancelAtPeriodEnd($subscription->transactionId);
            $willBeCanceled = $subscriptionScheduleDTO->willBeCanceled();
        } else {
            $subscriptionDTO = container(SubscriptionService::class)
                ->cancelAtPeriodEnd($subscription->transactionId);
            $willBeCanceled = $subscriptionDTO->willBeCanceled();
        }

        if (! $willBeCanceled) {
            throw new Exception('Subscription will not be canceled at the end of the current period');
        }

        /**
         * Fires after a subscription is scheduled to be canceled at the period end.
         *
         * @since 1.8.0
         * @hook stellarpay_subscription_scheduled_to_cancel_at_period_end
         * @param Subscription $subscription The subscription that will be canceled at the end of the current period.
         */
        Hooks::doAction('stellarpay_subscription_scheduled_to_cancel_at_period_end', $subscription);

        return true;
    }

    /**
     * @since 1.5.0 Use getSubscriptionProductsFromCart
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function cartContainsSubscription(): bool
    {
        return ! empty($this->getSubscriptionProductsFromCart());
    }

    /**
     * @since 1.5.0
     * @return array<CartItemDTO>
     * @throws BindingResolutionException
     */
    protected function getSubscriptionProductsFromCart(): array
    {
        if (empty(WC()->cart->get_cart_contents())) {
            return [];
        }

        $subscriptionProducts = [];
        foreach (WC()->cart->get_cart_contents() as $cartItem) {
            $product = $cartItem['data'];

            if (! ($product instanceof WC_Product)) {
                continue;
            }

            if (!$this->isSubscriptionProduct($product)) {
                continue;
            }

            $subscriptionProducts[] = CartItemDTO::fromWooCartItem($cartItem);
        }

        return $subscriptionProducts;
    }

    /**
     * @since 1.8.0 Adds support to variable products.
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    protected function isSubscriptionProduct(WC_Product $product): bool
    {
        switch (true) {
            case $product instanceof WC_Product_Variable:
                $productType = container(ProductVariableRepository::class)->getProductType($product);
                break;

            case $product instanceof WC_Product_Variation:
                $productType = container(ProductVariationRepository::class)->getProductType($product);
                break;

            default:
                $productType = container(ProductRepository::class)->getProductType($product);
                break;
        }

        if (! $productType) {
            return false;
        }

        return $productType->isSubscriptionPayments() || $productType->isInstallmentPayments();
    }

    /**
     * Get the price html for the order item.
     *
     * @since 1.0.0
     */
    protected function getPriceHtml(WC_Order_Item_Product $orderItem, Subscription $subscription): string
    {
        $order = wc_get_order($orderItem->get_order_id());

        $orderItemProductDecorator = new OrderItemProductDecorator($orderItem, $order);
        $price = $orderItemProductDecorator->getSubscriptionAmount()->getAmount();

        return sprintf(
            '%1$s / %2$s',
            wc_price($price),
            $this->getFormattedBillingPeriod($subscription)
        );
    }

    /**
     * Get the formatted billing period.
     *
     * @since 1.3.0 Use renamed function.
     * @since 1.0.0
     */
    protected function getFormattedBillingPeriod(Subscription $subscription): string
    {
        return sprintf(
            // translators: %1$s is the frequency, %2$s is the period. Example: `3 months`.
            _x('%1$s %2$s', 'Subscription billing period', 'stellarpay'),
            1 === $subscription->frequency ? '' : $subscription->frequency,
            $subscription->period->getLabelByFrequency($subscription->frequency),
        );
    }

    /**
     * @since 1.4.0
     * @throws BindingResolutionException
     */
    protected function getSubscriptionProratedAmount(Subscription $subscription): Money
    {
        $currency = strtolower(get_woocommerce_currency());

        if (! $subscription->transactionId) {
            return Money::make(0, $currency);
        }

        if ($subscription->isScheduleType()) {
            return Money::make($this->calculateProrationAmountManually($subscription), $currency);
        }

        $invoice = container(InvoiceService::class)
            ->getUpcomingInvoiceForSubscription($subscription->transactionId);

        return Money::fromMinorAmount(absint($invoice->getTotal()), $currency);
    }

    /**
     * Calculate proration amount manualy.
     *
     * Relying on Stripe API (`invoices/upcoming` endpoint) is preferable when calculating
     * the prorated amount. However, for scheduled subscriptions this is not possible.
     *
     * @since 1.4.0
     */
    protected function calculateProrationAmountManually(Subscription $subscription): float
    {
        if ($subscription->getLastOrderAmount()->getAmount() <= 0) {
            return 0;
        }

        $createdAt = $subscription->createdAtGmt->getTimestamp();
        $nextBillingAt = $subscription->nextBillingAtGmt->getTimestamp();
        $now = Temporal::getCurrentDateTime()->getTimestamp();

        if ($now >= $nextBillingAt || $createdAt >= $nextBillingAt) {
            return 0;
        }

        $remainingPercentualTime = ($nextBillingAt - $now) / ($nextBillingAt - $createdAt);

        if ($remainingPercentualTime <= 0) {
            return 0;
        }

        $prorationAmount = $this->getSubscriptionAmount($subscription)->getAmount() * $remainingPercentualTime;

        return round($prorationAmount, 2);
    }

    /**
     * @since 1.4.0
     * @throws BindingResolutionException
     */
    protected function getSubscriptionLastChargeId(Subscription $subscription): ?string
    {
        $lastInvoice = $subscription->isScheduleType()
            ? null
            : container(InvoiceService::class)->getLastPaidInvoiceForSubscription($subscription->transactionId);

        if (null === $lastInvoice) {
            return $this->getSubscriptionOrderChargeId($subscription);
        }

        return $lastInvoice->getChargeId();
    }

    /**
     * @since 1.4.0
     * @throws BindingResolutionException
     */
    protected function getSubscriptionOrderChargeId(Subscription $subscription): ?string
    {
        $siteUrl = esc_url(get_site_url());
        $parameters = [
            'limit' => 1,
            'query' => "metadata['order_id']:'$subscription->firstOrderId' AND metadata['site_url']: '$siteUrl'"
        ];

        $charges = container(ChargeService::class)->searchCharges($parameters);

        if (empty($charges[0])) {
            return null;
        }

        return $charges[0]->getId();
    }

    /**
     * @since 1.4.0
     * @throws BindingResolutionException|StripeAPIException
     */
    protected function refundSubscription(Subscription $subscription, RefundType $refundType): ?StripeResponseRefundDTO
    {
        switch ($refundType->getValue()) {
            case RefundType::LAST_PAYMENT:
                $chargeId = $this->getSubscriptionLastChargeId($subscription);
                $amount = $this->getSubscriptionAmount($subscription)->getMinorAmount();
                break;

            case RefundType::PRORATED_AMOUNT:
                $chargeId = $this->getSubscriptionLastChargeId($subscription);
                $amount = $this->getSubscriptionProratedAmount($subscription)->getMinorAmount();
                break;

            default:
                return null;
        }

        if (!$chargeId) {
            return null;
        }

        $refundData = RefundDTO::fromArray(
            [
                'chargeId' => $chargeId,
                'amount' => $amount,
                'metadata' => [
                    RefundEventDTO::REFUNDED_BY_STELLARPAY_METADATA_KEY => ModifierContextType::ADMIN,
                    RefundEventDTO::SUBSCRIPTION_ID_METADATA_KEY => $subscription->id,
                    RefundEventDTO::REFUND_TYPE_METADATA_KEY => $refundType->getValue(),
                ]
            ]
        );

        return container(RefundService::class)->createRefund($refundData);
    }

    /**
     * Get the subscription amount.
     *
     * Util when the order has more than one subscription and
     * you want to retrieve the individual subscription amount.
     *
     * @since 1.4.0
     */
    protected function getSubscriptionAmount(Subscription $subscription): Money
    {
        $orderItem = $subscription->getLastOrder()->get_item($subscription->firstOrderItemId);

        if (! ($orderItem instanceof WC_Order_Item_Product)) {
            return Money::make(0, $subscription->getLastOrder()->get_currency());
        }

        $orderItemProductDecorator = new OrderItemProductDecorator($orderItem, $subscription->getLastOrder());

        return $orderItemProductDecorator->getSubscriptionAmount();
    }

    /**
     * Get the cart billing period (e.g. `monthly`) based on the
     * product in the cart.
     *
     * All products in the cart should have the same billing period.
     * Otherwise, an empty string is returned.
     *
     * @since 1.8.0 Move from Product class.
     *
     * @throws BindingResolutionException
     */
    protected function getBillingPeriodForCart(WC_Cart $cart): ?string
    {
        if (1 < count($cart->get_cart_contents())) {
            return null;
        }

        $cartItem = current($cart->get_cart_contents());
        $product = $cartItem['data'] ?? false;

        if (! is_a($product, 'WC_Product')) {
            return null;
        }

        $product = ProductFactory::makeFromProduct($product);

        if (! $product) {
            return null;
        }

        if ($product->getProductType()->isOnetimePayments()) {
            return null;
        }

        return $product->getFormattedBillingPeriod();
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    protected function hasAtLeastOneProductOnSaleInCart(): bool
    {
        if (! $this->cartContainsSubscription()) {
            return false;
        }

        $cartItems = $this->getSubscriptionProductsFromCart();

        return array_reduce(
            $cartItems,
            static function (bool $carry, CartItemDTO $cartItem) {
                return $carry || $cartItem->product->isOnSale();
            },
            false
        );
    }
}
