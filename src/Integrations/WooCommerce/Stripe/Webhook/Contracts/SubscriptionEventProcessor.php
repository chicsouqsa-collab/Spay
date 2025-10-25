<?php

/**
 * This class is used as contract for classes which process the Stripe subscription events.
 * For example: customer.subscription.updated, customer.subscription.deleted, etc
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Services\ModifierContextService;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\SubscriptionEventDTO;
use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;

/**
 * Class SubscriptionEventProcessor
 *
 * @since 1.1.0 Make compatible with update EventProcessor class.
 * @since 1.0.0
 */
abstract class SubscriptionEventProcessor extends EventProcessor
{
    use SubscriptionUtilities;

    /**
     * This method processes the Stripe webhook.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function processEvent(): EventResponse
    {
        $eventDTO = $this->getEventDTO();
        $subscription = $this->getSubscriptionByEvent($eventDTO);

        $this->eventResponse->setWebhookEventSourceType(WebhookEventSource::STELLARPAY_SUBSCRIPTION());

        if (! $subscription instanceof Subscription) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }

        $subscriptionEvent = SubscriptionEventDTO::fromEvent($eventDTO);

        $this->processSubscription($subscription, $subscriptionEvent);

        return $this->eventResponse
            ->setWebhookEventSourceId($subscription->id)
            ->ensureResponse();
    }

    /**
     * This method processes the event for the woocommerce order.
     *
     * @since 1.0.0
     */
    abstract protected function processSubscription(Subscription $subscription, SubscriptionEventDTO $subscriptionEvent): void;

    /**
     * This function returns the order that matches the event.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function getSubscriptionByEvent(EventDTO $event): ?Subscription
    {
        return Subscription::findByTransactionId($event->getObjectId());
    }

    /**
     * Add order note on subscription status change. Try to retrieve the modifier context type from the transient.
     *
     * @since 1.4.0
     * @throws Exception
     */
    public function addStatusChangeOrderNote(Subscription $subscription): void
    {
        $modifierContextState = new ModifierContextService($this->getEventDTO()->getType(), $subscription->id);
        $modifierContextType = $modifierContextState->getModifierContextType() ?? ModifierContextType::WEBHOOK();

        OrderNote::onSubscriptionStatusChange($subscription, $modifierContextType);
        $modifierContextState->removeContext();
    }
}
