<?php

/**
 * UncaughtExceptionLogger
 *
 * This class is responsible for logging logical exceptions
 *
 * @package StellarPay\Core\Exceptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Exceptions\Primitives;

use StellarPay\Core\Exceptions\Contracts\LoggableException;
use StellarPay\Core\Exceptions\Traits\Loggable;

/**
 * Class LogicException
 *
 * @since 1.0.0
 */
class LogicException extends \LogicException implements LoggableException
{
    use Loggable;
}
