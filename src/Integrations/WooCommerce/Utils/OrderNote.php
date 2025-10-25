<?php

/**
 * This class is responsible for adding notes to the WooCommerce orders.
 *
 * @package StellarPay\Integrations\WooCommerce\Utils
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Utils;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.3.0 Remove "cancelSubscriptionNote". Use "subscriptionStatusChangedNote"
 * @since 1.1.0
 */
class OrderNote
{
    /**
     * @since 1.3.0
     * @throws Exception
     */
    public static function onSubscriptionStatusChange(Subscription $subscription, ModifierContextType $modifierContextType): void
    {
        $note = sprintf(
            'Subscription #%1$s status changed to "%2$s" by %3$s.',
            $subscription->id,
            $subscription->status->label(),
            $modifierContextType->label()
        );

        $order = wc_get_order($subscription->firstOrderId);
        $order->add_order_note($note);
    }

    /**
     * Add order note when default payment method is updated for subscription.
     *
     * @since 1.3.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public static function onSubscriptionPaymentMethodUpdate(Subscription $subscription, string $token, ModifierContextType $modifierContextType): void
    {
        $order = self::getOrder($subscription);
        $paymentMethodTitle = container(PaymentMethodRepository::class)->getPaymentMethodTitleForReceipt(
            $token,
            $order
        );

        $order->add_order_note(
            sprintf(
            /* translators: 1: Credit card type and last 4 digits */
                esc_html__(
                    'Subscription #%1$s payment method updated to %2$s by %3$s. Stripe method method id is %4$s.',
                    'stellarpay'
                ),
                $subscription->id,
                $paymentMethodTitle,
                $modifierContextType->label(),
                $token
            )
        );
    }

    /**
     * Add order note when subscription is scheduled to be canceled.
     *
     * @since 1.3.0
     * @throws Exception
     */
    public static function onScheduleSubscriptionCancelation(Subscription $subscription, ModifierContextType $modifierContextType): void
    {
        $order = self::getOrder($subscription);

        $expiresAt = $subscription->expiresAt
            ? Temporal::getWPFormattedDate($subscription->expiresAt)
            : esc_html__('the end of the current period', 'stellarpay');

        $order->add_order_note(
            sprintf(
                // translators: 1 - subscription ID; 2 - date or default message `the end of the current period`.
                esc_html__('Subscription #%1$s is scheduled to be canceled at %2$s by %3$s.', 'stellarpay'),
                $subscription->id,
                $expiresAt,
                $modifierContextType->label()
            )
        );
    }

    /**
     * Add an order note when a new subscription is scheduled.
     *
     * @since 1.5.0
     * @throws Exception
     */
    public static function onScheduleSubscriptionCreation(Subscription $subscription): void
    {
        $order = self::getOrder($subscription);

        $order->add_order_note(
            sprintf(
                /* translators: 1: subscription ID 2: Subscripton creation date 3: Subscription renewal date 4: Stripe subscription schedule id */
                esc_html__('Subscription #%1$s is created at %2$s. First renewal date for subscription is %3$s. Stripe subscription schedule id is %4$s', 'stellarpay'),
                $subscription->id,
                Temporal::getWPFormattedDate($subscription->createdAt),
                Temporal::getWPFormattedDate($subscription->nextBillingAt),
                $subscription->transactionId
            )
        );
    }

    /**
     * Add an order note when a subscription is scheduled to be paused at period end.
     *
     * @since 1.9.0
     * @throws Exception
     */
    public static function onSubscriptionPausedAtPeriodEnd(Subscription $subscription, ModifierContextType $modifierContextType): void
    {
        $order = self::getOrder($subscription);
        $order->add_order_note(
            sprintf(
                // translators: 1 - subscription ID; 2 - modifier context type (Admin, Customer, Webhook, etc).
                esc_html__('Subscription #%1$s is scheduled to be paused at period end by %2$s.', 'stellarpay'),
                $subscription->id,
                $modifierContextType->label()
            )
        );
    }

    /**
     * @since 1.3.0
     * @throws Exception
     */
    protected static function getOrder(Subscription $subscription): WC_Order
    {
        $order = wc_get_order($subscription->firstOrderId);

        if ($order) {
            return $order;
        }

        throw new Exception(
            esc_html__(
                'It was not possible to retrieve the WooCommerce order.',
                'stellarpay'
            )
        );
    }
}
