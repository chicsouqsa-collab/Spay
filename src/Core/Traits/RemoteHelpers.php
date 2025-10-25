<?php

/**
 * This trait provides functions to request third party api.
 *
 * This allows mocking request functions in class.
 *
 * @package StellarPay\Core
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Traits;

use StellarPay\Core\Exceptions;
use WP_Error;

use function StellarPay\Core\remote_get;

/**
 * @since 1.2.0
 */
trait RemoteHelpers
{
    /**
     * @since 1.2.0
     *
     * @return array|WP_Error
     * @throws Exceptions\Primitives\Exception
     */
    protected function remoteGet(string $url, array $args = [])
    {
        return remote_get($url, $args);
    }
}
