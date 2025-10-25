<?php

/**
 * Event processor for Stripe event for the woocommerce order.
 *
 * This class is a contract for the Stripe event processor linked to the woocommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\WooCommerce\Stripe\Decorators\OrderDecorator;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use WC_Order;

/**
 * Class OrderEventProcessor
 *
 * @since 1.1.0 Make compatible with update EventProcessor class
 * @since 1.0.0
 */
abstract class OrderEventProcessor extends EventProcessor
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * OrderEventProcessor constructor.
     */
    public function __construct(EventResponse $eventResponse, OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;

        parent::__construct($eventResponse);
    }

    /**
     * This method processes the Stripe webhook.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function processEvent(): EventResponse
    {
        $eventDTO = $this->getEventDTO();
        $order = $this->getOrderByEvent($eventDTO);

        $this->eventResponse->setWebhookEventSourceType(WebhookEventSource::WOO_ORDER());

        if (! $order instanceof WC_Order) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }

        $orderDecorator = new OrderDecorator($order);

        if (
            ! $orderDecorator->isMatchPaymentMethod()
            || ! $this->isOrderInAcceptableStatus($order)
        ) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::UNPROCESSABLE())
                ->ensureResponse();
        }

        if ($this->isSubscription($order)) {
            $this->processSubscriptions($order, $eventDTO);
        }

        $this->processOrder($order, $eventDTO);

        return $this->eventResponse
            ->setWebhookEventSourceId($order->get_id())
            ->ensureResponse();
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function processSubscriptions(WC_Order $order, EventDTO $event): void
    {
        $subscriptions = $this->getSubscriptionsForOrder($order);

        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription, $order, $event);
        }
    }

    /**
     * This method processes the event for the woocommerce order.
     *
     * @since 1.0.0
     * @throws Exception
     */
    abstract protected function processOrder(WC_Order $order, EventDTO $eventDTO): void;

    /**
     * @since 1.0.0
     */
    protected function processSubscription(Subscription $subscription, WC_Order $order, EventDTO $eventDTO): void
    {
    }

    /**
     * This function returns the order that matches the event.
     *
     * @since 1.0.0
     */
    protected function getOrderByEvent(EventDTO $event): ?WC_Order
    {
        return $this->orderRepository->getOrderByPaymentIntentId($event->getPaymentIntentId());
    }

    /**
     * This function returns whether order has acceptable status.
     *
     * @since 1.6.0 Use OrderStatus.
     * @since 1.0.0
     */
    protected function isOrderInAcceptableStatus(WC_Order $order): bool
    {
        return in_array($order->get_status(), [OrderStatus::PENDING, OrderStatus::FAILED], true);
    }
}
