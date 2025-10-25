<?php

/**
 * Enum for proration behavior.
 * https://docs.stripe.com/billing/subscriptions/prorations#control-proration-behavior
 *
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.9.0
 *
 * @method static ProrationBehavior ALWAYS_INVOICE()
 * @method static ProrationBehavior CREATE_PRORATIONS()
 * @method static ProrationBehavior NONE()
 */
class ProrationBehavior extends Enum
{
    /**
     * @since 1.9.0
     */
    public const ALWAYS_INVOICE = 'always_invoice';

    /**
     * @since 1.9.0
     */
    public const CREATE_PRORATIONS = 'create_prorations';

    /**
     * @since 1.9.0
     */
    public const NONE = 'none';
}
