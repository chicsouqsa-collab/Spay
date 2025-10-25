<?php

/**
 * Controller Contract.
 *
 * This file provides a contract for data strategy class.
 * Use this contract class when returning data in array format to service which requests external apis.
 *
 * @package StellarPay\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

/**
 * @since 1.0.0
 */
interface DataStrategy
{
    /**
     * @since 1.0.0
     */
    public function generateData(): array;
}
