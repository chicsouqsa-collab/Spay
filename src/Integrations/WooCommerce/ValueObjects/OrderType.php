<?php

/**
 * This class is responsible for providing enum for the woocommerce order type.
 *
 * @package StellarPay\Integrations\WooCommerce\ValueObjects
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.1.0
 * @method static OrderType ONETIME()
 * @method static OrderType SUBSCRIPTION()
 * @method bool isOnetime()
 * @method bool isSubscription()
 */
class OrderType extends Enum
{
    /**
     * @since 1.1.0
     */
    public const ONETIME = 'onetime ';

    /**
     * @since 1.1.0
     */
    public const SUBSCRIPTION = 'subscription';
}
