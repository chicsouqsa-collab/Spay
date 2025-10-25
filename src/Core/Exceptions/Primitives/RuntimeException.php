<?php

/**
 * UncaughtExceptionLogger
 *
 * This class is responsible for logging uncaught exceptions
 *
 * @package StellarPay\Core\Exceptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Exceptions\Primitives;

use StellarPay\Core\Exceptions\Contracts\LoggableException;
use StellarPay\Core\Exceptions\Traits\Loggable;

/**
 * Class RuntimeException
 *
 * @since 1.0.0
 */
class RuntimeException extends \RuntimeException implements LoggableException
{
    use Loggable;
}
