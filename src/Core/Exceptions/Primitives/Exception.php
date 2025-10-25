<?php

/**
 * Exception
 *
 * This class is responsible for handling exceptions.
 *
 * @package StellarPay\Core\Exceptions\Primitives
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Exceptions\Primitives;

use StellarPay\Core\Exceptions\Contracts\LoggableException;
use StellarPay\Core\Exceptions\Traits\Loggable;

/**
 * Class Exception
 *
 * @since 1.0.0
 */
class Exception extends \Exception implements LoggableException
{
    use Loggable;
}
