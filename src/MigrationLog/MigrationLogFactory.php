<?php

/**
 * @package StellarPay\MigrationLog\Factories
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use StellarPay\Core\Contracts\ModelFactory;

/**
 * @since 1.2.0
 */
class MigrationLogFactory extends ModelFactory
{
    /**
     * @since 1.2.0
     * @return array
     */
    public function definition(): array
    {
        return [
            'status' => MigrationLogStatus::FAILED(),
        ];
    }
}
