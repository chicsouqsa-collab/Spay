<?php

/**
 * This class handles pausing a subscription schedule by updating the start date of the schedule.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use DateTime;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\UpdateSubscriptionScheduleDataStrategy;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionScheduleDTO;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService as BaseSubscriptionScheduleService;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item_Product;

use function StellarPay\Core\container;

/**
 * @since 1.9.0
 */
class SubscriptionScheduleService
{
    /**
     * @since 1.9.0
     */
    protected BaseSubscriptionScheduleService $baseSubscriptionScheduleService;

    /**
     * PauseSubscriptionSchedule constructor.
     */
    public function __construct(BaseSubscriptionScheduleService $baseSubscriptionScheduleService)
    {
        $this->baseSubscriptionScheduleService = $baseSubscriptionScheduleService;
    }

    /**
     * @since 1.9.0
     * @throws Exception|BindingResolutionException
     */
    public function pause(Subscription $subscription, DateTime $resumesAt): bool
    {
        $this->updateSubscriptionScheduleStartDateInStripe($subscription, $resumesAt);
        $subscription->pauseAtPeriodEnd($resumesAt);

        OrderNote::onSubscriptionPausedAtPeriodEnd($subscription, ModifierContextType::ADMIN());

        return true;
    }

    /**
     * @since 1.9.0
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function resume(Subscription $subscription): bool
    {
        $newStartDate = $this->getResumeDate($subscription);

        $this->updateSubscriptionScheduleStartDateInStripe($subscription, $newStartDate);

        $result = $subscription->resume();

        OrderNote::onSubscriptionStatusChange($subscription, ModifierContextType::ADMIN());

        return $result;
    }

    /**
     * Get the resume date for the subscription.
     *
     * If we are resuming the subscription before its original next billing date has
     *  passed, then, do not change the next billing date/resume date.
     *
     * If subscription is resumed after the original billing date has passed, then set the resume date to today.
     *
     * @since 1.9.0
     */
    private function getResumeDate(Subscription $subscription): DateTime
    {
        $nextBillingDate = $subscription->nextBillingAt;

        // if the next billing date is in the past, then set the start date to today.
        if ($nextBillingDate < Temporal::getCurrentDateTime()) {
            return Temporal::getCurrentDateTime();
        }

        return $nextBillingDate;
    }

    /**
     * Update the subscription schedule start date.
     *
     * @since 1.9.0
     * @throws Exception|BindingResolutionException
     */
    private function updateSubscriptionScheduleStartDateInStripe(Subscription $subscription, DateTime $newStartDate): void
    {
        $order = wc_get_order($subscription->firstOrderId);
        if (! $order instanceof WC_Order) {
            throw new Exception('Order not found for subscription.');
        }

        $orderItem = $order->get_item($subscription->firstOrderItemId);
        if (! $orderItem instanceof WC_Order_Item_Product) {
            throw new Exception('Order item not found for subscription.');
        }

        // Prepare update schedule data
        $subscriptionScheduleDataStrategy = container(UpdateSubscriptionScheduleDataStrategy::class);
        $subscriptionScheduleDataStrategy
            ->setOrder($order)
            ->setOrderItem($orderItem)
            ->setSubscription($subscription)
            ->setStartDate($newStartDate);

        $subscriptionDTO = SubscriptionScheduleDTO::fromDataStrategy($subscriptionScheduleDataStrategy);

        $this->baseSubscriptionScheduleService->update($subscription->transactionId, $subscriptionDTO);
    }
}
