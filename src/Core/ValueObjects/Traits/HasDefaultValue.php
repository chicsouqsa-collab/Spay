<?php

/**
 * @package StellarPay\Core\ValueObjects\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects\Traits;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.8.0
 */
trait HasDefaultValue
{
    /**
     * @since 1.8.0
     * @throws Exception
     */
    public static function defaultValue(): self
    {
        throw new Exception('Not implemented');
    }
}
