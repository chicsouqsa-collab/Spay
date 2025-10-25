<?php

/**
 * PaymentIntentProcessing event processor for Stripe.
 *
 * This class is responsible for processing the payment_intent.processing event from Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events
 * @since 1.6.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events;

use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts\OrderEventProcessor;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use WC_Order;

/**
 * @since 1.6.0
 */
class PaymentIntentProcessing extends OrderEventProcessor
{
    /**
     * @since 1.6.0
     */
    protected function processOrder(WC_Order $order, EventDTO $eventDTO): void
    {
        $order->update_status(OrderStatus::ON_HOLD);
    }
}
