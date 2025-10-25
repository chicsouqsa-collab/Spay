<?php

/**
 * @package StellarPay\Core\ValueObjects\Traits
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects\Traits;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.3.0
 *
 * @method string getValue()
 */
trait HasLabels
{
    /**
     * @since 1.3.0
     * @throws Exception
     */
    public function getLabelBy(string $key): string
    {
        return self::labels()[$key];
    }

    /**
     * Return an associative array of labels for values.
     * @since 1.3.0
     * @return array<string, string>
     * @throws Exception
     */
    public static function labels(): array
    {
        throw new Exception('Not implemented');
    }

    /**
     * Get the human-readable label for a specific value.
     *
     * @since 1.3.0
     * @return string
     * @throws Exception
     */
    public function label(): string
    {
        return self::labels()[ $this->getValue() ];
    }
}
