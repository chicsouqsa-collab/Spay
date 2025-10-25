<?php

/**
 * @package StellarPay\Core\ValueObjects\Traits
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects\Traits;

use StellarPay\Core\Exceptions\Primitives\Exception;

/**
 * @since 1.4.0
 *
 * @method array labels()
 */
trait HasOptions
{
    /**
     * @since 1.4.0
     * @throws Exception
     */
    public static function getOptions(): array
    {
        $labels = self::labels(); // @phpstan-ignore-line

        $result = [];
        foreach ($labels as $key => $label) {
            $result[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $result;
    }
}
