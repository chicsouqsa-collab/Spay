<?php

/**
 * PaymentIntentCanceled event processor for Stripe.
 *
 * This class is responsible for processing the payment_intent.canceled event from Stripe.
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
 * Class PaymentIntentCanceled
 *
 * @since 1.8.0 Delete subscription when payment canceled.
 * @since 1.0.0
 */
class PaymentIntentCanceled extends OrderEventProcessor
{
    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function processOrder(WC_Order $order, EventDTO $eventDTO): void
    {
        $order->update_status(OrderStatus::CANCELED);

        $orderDecorator = new OrderDecorator($order);
        $orderDecorator->deleteAllSubscriptions();
    }
}
