<?php

/**
 * This class is responsible to provide enum for a subscription product type.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.0.0
 *
 * @method static self INSTALLMENT_PAYMENTS()
 * @method static self SUBSCRIPTION_PAYMENTS()
 * @method static self ONETIME_PAYMENTS()
 * @method bool isInstallmentPayments()
 * @method bool isSubscriptionPayments()
 * @method bool isOnetimePayments()
 */
class SubscriptionProductType extends Enum
{
    /**
     * @since 1.0.0
     */
    public const INSTALLMENT_PAYMENTS = 'installmentPayments';

    /**
     * @since 1.0.0
     */
    public const  SUBSCRIPTION_PAYMENTS = 'subscriptionPayments';

    /**
     * @since 1.0.0
     */
    public const ONETIME_PAYMENTS = 'onetimePayments';
}
