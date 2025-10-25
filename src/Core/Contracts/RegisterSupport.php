<?php

/**
 * This class is a contract for registering support for a feature.
 *
 * @package StellarPay\Core\Contracts
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

/**
 * @since 1.8.0
 */
interface RegisterSupport
{
    /**
     * Register support for the feature.
     *
     * @since 1.8.0
     */
    public function registerSupport(): void;
}
