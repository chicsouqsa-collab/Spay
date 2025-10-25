<?php

/**
 * This exception class should be throw if get the invalid statement descriptor
 *
 * @package StellarPay\PaymentGateways\Stripe\Exceptions
 * @since 1.6.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Exceptions;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.6.0
 */
class InvalidSettingValueException extends Exception
{
}
