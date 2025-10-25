<?php

/**
 * This class is responsible for adding notes to the WooCommerce subscriptions.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Utils
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Utils;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use WC_Subscription;

use function StellarPay\Core\container;

/**
 * @since 1.7.0
 */
class WooSubscriptionNote
{
    /**
     * Add order note when default payment method is updated for subscription.
     *
     * @since 1.3.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public static function onSubscriptionPaymentMethodUpdate(WC_Subscription $subscription, string $token, ModifierContextType $modifierContextType)
    {
        $paymentMethodTitle = container(PaymentMethodRepository::class)->getPaymentMethodTitleForReceipt($token, $subscription);

        $subscription->add_order_note(
            sprintf(
            /* translators: 1: Credit card type and last 4 digits */
                esc_html__(
                    'Subscription\'s payment method updated to %2$s by %3$s. Stripe method method id is %4$s.',
                    'stellarpay'
                ),
                $subscription->get_id(),
                $paymentMethodTitle,
                $modifierContextType->label(),
                $token
            )
        );
    }
}
