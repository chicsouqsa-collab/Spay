<?php

/**
 * This class uses to create subscription schedule on the Stripe on the Stripe payment completion.
 * We create a subscription upon successful payment or order with a subscription with zero initial order value.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.5.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\SubscriptionScheduleDataStrategy;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionScheduleDTO;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.5.0
 */
class CreateStripeSubscriptionSchedule
{
    /**
     * @since 1.5.0
     */
    protected SubscriptionScheduleService $subscriptionScheduleService;

    /**
     * @siince 1.6.0
     */
    public function __construct(SubscriptionScheduleService $subscriptionScheduleService)
    {
        $this->subscriptionScheduleService = $subscriptionScheduleService;
    }

    /**
     * @since 1.5.0
     *
     * @throws BindingResolutionException|Exception|\Exception
     */
    public function __invoke(Subscription $subscription, \WC_Order $order = null): void
    {
        $order = $order ?? wc_get_order($subscription->firstOrderId);

        $this->processSubscription($subscription, $order);
    }

    /**
     * @since 1.5.0
     *
     * @throws BindingResolutionException|Exception|\Exception
     */
    protected function processSubscription(Subscription $subscription, WC_Order $order): void
    {
        $this->updateSubscription($subscription);

        /* @var \WC_Order_Item_Product $subscriptionOrderItem Order item product. */
        $subscriptionOrderItem = $order->get_item($subscription->firstOrderItemId);

        $subscriptionScheduleDataStrategy = container(SubscriptionScheduleDataStrategy::class);
        $subscriptionScheduleDataStrategy
            ->setSubscription($subscription)
            ->setOrder($order)
            ->setOrderItem($subscriptionOrderItem);

        $subscriptionDTO = SubscriptionScheduleDTO::fromDataStrategy($subscriptionScheduleDataStrategy);
        $subscriptionSchedule = $this->subscriptionScheduleService->create($subscriptionDTO);

        $subscription->transactionId = $subscriptionSchedule->getId();
        $subscription->save();

        OrderNote::onScheduleSubscriptionCreation($subscription);
    }

    /**
     * @since 1.5.0
     */
    protected function updateSubscription(Subscription $subscription): void
    {
        $newStatus = SubscriptionStatus::ACTIVE();

        if ($subscription->status->equals($newStatus)) {
            return;
        }

        $subscription->status = $newStatus;

        $subscription->startedAt = Temporal::getCurrentDateTime();
        $subscription->startedAtGmt = Temporal::getGMTDateTime($subscription->startedAt);

        $subscription->billedCount = 1;

        $subscription->nextBillingAt = $subscription->calculateNextBillingDate();
        $subscription->nextBillingAtGmt = Temporal::getGMTDateTime($subscription->nextBillingAt);

        if ($subscription->hasEndDate()) {
            $subscription->endedAt = $subscription->calculateEndDate();
            $subscription->endedAtGmt = Temporal::getGMTDateTime($subscription->endedAt);
        }
    }
}
