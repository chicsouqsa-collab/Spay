<?php

/**
 * This class is used to represent the result of the payment process.
 * 
 * @package StellarPay\Integrations\WooCommerce\ValueObjects
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.7.0
 * 
 * @method static self SUCCESS()
 * @method static self FAILURE()
 */
class ProcessPaymentResultType extends Enum
{
    /**
     * @since 1.7.0
     */
    public const SUCCESS = 'success';

    /**
     * @since 1.7.0
     */
    public const FAILURE = 'failure';
}
