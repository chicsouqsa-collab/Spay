<?php

/**
 * PaymentIntentPaymentFailed event processor for Stripe.
 *
 * This class is responsible for processing the payment_intent.payment_failed event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Decorators\OrderDecorator;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderEventProcessor;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use WC_Order;

/**
 * Class PaymentIntentPaymentFailed
 *
 * @since 1.8.0 Use order decorator
 * @since 1.2.0 Remove subscriptions associated with the failed order
 * @since 1.0.0
 */
class PaymentIntentPaymentFailed extends OrderEventProcessor
{
    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function processOrder(WC_Order $order, EventDTO $eventDTO): void
    {
        $order->update_status(OrderStatus::FAILED);

        $orderDecorator = new OrderDecorator($order);
        $orderDecorator->deleteAllSubscriptions();
    }
}
