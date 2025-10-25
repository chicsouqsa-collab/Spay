<?php

/**
 * This class is responsible for processing the customer.subscription.canceled event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\SubscriptionScheduleEventDTO;
use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;

/**
 * @since 1.1.0 Make compatible with update EventProcessor clas
 * @since 1.0.0
 */
class CustomerSubscriptionCreated extends EventProcessor
{
    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function processEvent(): EventResponse
    {
        $eventDTO = $this->getEventDTO();
        $subscriptionEventDTO = SubscriptionScheduleEventDTO::fromEvent($eventDTO);
        $subscription = $this->getSubscriptionFromEvent($subscriptionEventDTO);

        $this->eventResponse->setWebhookEventSourceType(WebhookEventSource::STELLARPAY_SUBSCRIPTION());

        if (! ( $subscription instanceof Subscription)) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }

        $subscription->transactionId = $subscriptionEventDTO->getObjectId();
        $subscription->save();

        $order = wc_get_order($subscription->firstOrderId);
        $orderItem = $order->get_item($subscription->firstOrderItemId);
        $order->add_order_note(
            sprintf(
                /* translators: 1: Subscription id 2: Order item name */
                esc_html__('Subscription #%1$s created for %2$s', 'stellarpay'),
                $subscription->id,
                $orderItem->get_name()
            )
        );

        return $this->eventResponse
            ->setWebhookEventSourceId($subscription->id)
            ->ensureResponse();
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function getSubscriptionFromEvent(SubscriptionScheduleEventDTO $subscriptionEventDTO): ?Subscription
    {
        $subscriptionScheduleId = $subscriptionEventDTO->getSubscriptionScheduleId();

        $subscription = $subscriptionScheduleId
            ? Subscription::findByTransactionId($subscriptionScheduleId)
            : $this->getSubscriptionFromMetadata($subscriptionEventDTO);

        $isSubscription = $subscription instanceof Subscription;

        if (! $isSubscription) {
            return null;
        }

        return $subscription;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function getSubscriptionFromMetadata(SubscriptionScheduleEventDTO $subscriptionEventDTO): ?Subscription
    {
        $subscriptionId = $subscriptionEventDTO->getSubscriptionId();
        $subscription = $subscriptionId ? Subscription::find($subscriptionId) : null;

        if (! ( $subscription instanceof Subscription )) {
            return null;
        }

        // Subscription should match first order id and order item id to prevent unwanted processing.
        if (
            $subscription->firstOrderId !== $subscriptionEventDTO->getOrderId()
            || $subscription->firstOrderItemId !== $subscriptionEventDTO->getOrderItemId()
        ) {
            return  null;
        }

        return $subscription;
    }
}
