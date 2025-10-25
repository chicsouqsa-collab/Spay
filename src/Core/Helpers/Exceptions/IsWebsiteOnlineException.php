<?php

/**
 * This exception trigger when use isWebsiteOnline helper function.
 *
 * WordPress adds a "php-error" class to "body" in the dashboard even if a PHP warning is suppressed.
 * WordPress has an option issue about this - https://core.trac.wordpress.org/ticket/51383
 *
 * We use this exception to prevent the WordPress from adding it.
 *
 * @package StellarPay\Core\Helpers\Exceptions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Helpers\Exceptions;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.0.0
 */
class IsWebsiteOnlineException extends Exception
{
}
