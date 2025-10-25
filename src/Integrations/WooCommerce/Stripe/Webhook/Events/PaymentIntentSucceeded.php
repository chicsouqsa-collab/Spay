<?php

/**
 * PaymentIntentSucceeded event processor for Stripe.
 *
 * This class is responsible for processing the payment_intent.succeeded event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use Exception;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\CreateStripeSubscriptionSchedule;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderEventProcessor;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;

use function StellarPay\Core\container;

/**
 * Class PaymentIntentSucceeded
 *
 * @since 1.6.0 Add function to get a list of acceptable order statuses.
 * @since 1.0.0
 */
class PaymentIntentSucceeded extends OrderEventProcessor
{
    /**
     * @since 1.0.0
     */
    protected SubscriptionScheduleService $subscriptionScheduleService;

    /**
     * @since 1.0.0
     */
    public function __construct(
        EventResponse $eventResponse,
        OrderRepository $orderRepository,
        SubscriptionScheduleService $subscriptionScheduleService
    ) {
        parent::__construct($eventResponse, $orderRepository);

        $this->subscriptionScheduleService = $subscriptionScheduleService;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function processOrder(WC_Order $order, EventDTO $eventDTO): void
    {
        $order->payment_complete($eventDTO->getPaymentIntentId());
    }

    /**
     * @since 1.8.0 Create subscription only if transaction id does not exist.
     * @since 1.5.0 Use the "CreateStripeSubscriptionSchedule" action.
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws \StellarPay\Core\Exceptions\Primitives\Exception
     * @throws Exception
     */
    protected function processSubscription(Subscription $subscription, WC_Order $order, EventDTO $eventDTO): void
    {
        if (is_null($subscription->transactionId)) {
            $createSubscriptionSchedule = container(CreateStripeSubscriptionSchedule::class);
            $createSubscriptionSchedule($subscription, $order);
        }
    }

    /**
     * @since 1.6.0.
     */
    protected function isOrderInAcceptableStatus(WC_Order $order): bool
    {
        return parent::isOrderInAcceptableStatus($order) || OrderStatus::ON_HOLD === $order->get_status();
    }
}
