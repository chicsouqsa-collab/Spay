<?php

/**
 * This class is responsible to provide enum for subscription source.
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
 * @method static SubscriptionStatus WOOCOMMERCE()
 * @method bool isWooCommerce()
 */
class SubscriptionSource extends Enum
{
    /**
     * @since 1.0.0
     */
    public const WOOCOMMERCE = 'WooCommerce';
}
