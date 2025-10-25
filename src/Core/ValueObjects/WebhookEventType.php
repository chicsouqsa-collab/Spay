<?php

/**
 * The Enum class to keep list of all the Stripe Webhook Events.
 *
 * @package StellarPay\PaymentGateways\Stripe\ValueObjects
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 *
 * @since 1.6.0 Add an event type
 * @since 1.3.0 Add an event type
 * @since 1.1.0
 *
 * @method static WebhookEventType ACCOUNT_UPDATED()
 * @method static WebhookEventType PAYMENT_INTENT_SUCCEEDED()
 * @method static WebhookEventType PAYMENT_INTENT_FAILED()
 * @method static WebhookEventType PAYMENT_INTENT_PROCESSING()
 * @method static WebhookEventType PAYMENT_INTENT_CANCELED()
 * @method static WebhookEventType CHARGE_REFUNDED()
 * @method static WebhookEventType CHARGE_UPDATED()
 * @method static WebhookEventType CHARGE_REFUND_UPDATED()
 * @method static WebhookEventType CUSTOMER_SUBSCRIPTION_UPDATED()
 * @method static WebhookEventType CUSTOMER_SUBSCRIPTION_CREATED()
 * @method static WebhookEventType CUSTOMER_SUBSCRIPTION_DELETED()
 * @method static WebhookEventType SUBSCRIPTION_SCHEDULE_CANCELED()
 * @method static WebhookEventType INVOICE_PAID()
 * @method static WebhookEventType PAYMENT_INTENT_CREATED()
 * @method static WebhookEventType INVOICE_PAYMENT_FAILED()
 */
class WebhookEventType extends Enum
{
    /**
     * @since 1.1.0
     */
    public const ACCOUNT_UPDATED = 'account.updated';

    /**
     * @since 1.1.0
     */
    public const PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';

    /**
     * @since 1.1.0
     */
    public const PAYMENT_INTENT_FAILED = 'payment_intent.payment_failed';

    /**
     * @since 1.1.0
     */
    public const PAYMENT_INTENT_CANCELED = 'payment_intent.canceled';

    /**
     * @since 1.6.0
     */
    public const PAYMENT_INTENT_PROCESSING = 'payment_intent.processing';

    /**
     * @since 1.1.0
     */
    public const CHARGE_REFUNDED = 'charge.refunded';

    /**
     * @since 1.1.0
     */
    public const CHARGE_UPDATED = 'charge.updated';

    /**
     * @since 1.1.0
     */
    public const CHARGE_REFUND_UPDATED = 'charge.refund.updated';

    /**
     * @since 1.1.0
     */
    public const CUSTOMER_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';

    /**
     * @since 1.1.0
     */
    public const CUSTOMER_SUBSCRIPTION_CREATED = 'customer.subscription.created';

    /**
     * @since 1.1.0
     */
    public const CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';

    /**
     * @since 1.1.0
     */
    public const SUBSCRIPTION_SCHEDULE_CANCELED = 'subscription_schedule.canceled';

    /**
     * @since 1.1.0
     */
    public const INVOICE_PAID = 'invoice.paid';

    /**
     * @since 1.3.0
     */
    public const INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';

    /**
     * @since 1.9.0
     */
    public const TEST_HELPER_TEST_CLOCK_READY = 'test_helpers.test_clock.ready';
}
