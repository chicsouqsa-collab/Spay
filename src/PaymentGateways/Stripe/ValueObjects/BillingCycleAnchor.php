<?php

/**
 * This class is responsible for handling the billing cycle anchor data.
 * https://docs.stripe.com/billing/subscriptions/billing-cycle
 *
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * Class BillingCycleAnchor
 *
 * @method static BillingCycleAnchor NOW()
 * @method static BillingCycleAnchor UNCHANGED()
 *
 * @since 1.9.0
 */
class BillingCycleAnchor extends Enum
{
    /**
     * @since 1.9.0
     */
    public const NOW = 'now';

    /**
     * @since 1.9.0
     */
    public const UNCHANGED = 'unchanged';
}
