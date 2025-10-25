<?php

/**
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions\Contracts;

/**
 * @since 1.2.0
 */
interface TestDataDeletionRule
{
    /**
     * @since 1.2.0
     */
    public function __invoke(): void;
}
