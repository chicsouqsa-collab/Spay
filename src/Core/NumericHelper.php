<?php

/**
 * @package StellarPay\Core
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

/**
 * @since 1.8.0
 */
class NumericHelper
{
    /**
     * Get one point less value while respecting the precision of the original number.
     *
     * @since 1.8.0
     */
    public static function getOnePointLessValue(float $value): float
    {
        // Determine the decimal precision based on numbers after the decimal point
        $valueAsString = (string)$value;
        $decimalPosition = strpos($valueAsString, '.');

        $precision = 2;
        if (false !== $decimalPosition) {
            $decimals = substr($valueAsString, $decimalPosition + 1);
            $precision = strlen($decimals);
        }

        // Subtract based on precision (e.g., 0.1, 0.01, 0.001 etc)
        $subtractAmount = 1 / (10 ** $precision);
        $result = $value - $subtractAmount;

        // Round the result to match the precision
        return round($result, $precision);
    }
}
