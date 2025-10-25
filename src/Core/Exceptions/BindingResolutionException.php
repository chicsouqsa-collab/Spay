<?php

/**
 * BindingResolutionException
 *
 * This class is responsible for handling binding resolution exceptions.
 *
 * @package StellarPay\Core\Exceptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Exceptions;

use Exception;
use StellarPay\Core\Exceptions\Contracts\LoggableException;
use StellarPay\Core\Exceptions\Traits\Loggable;

/**
 * Class BindingResolutionException.
 *
 * @since 1.0.0
 */
class BindingResolutionException extends Exception implements LoggableException
{
    use Loggable;
}
