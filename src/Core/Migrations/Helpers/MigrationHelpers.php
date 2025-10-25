<?php

/**
 * @package StellarPay\Core\Migrations\Helpers
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Migrations\Helpers;

use StellarPay\Core\Migrations\MigrationsRegister;

/**
 * @since 1.2.0
 *
 * @property MigrationsRegister $migrationsRegister
 *
 */
trait MigrationHelpers
{
    /**
     * @since 1.2.0
     */
    private function getMigrationsSorted(): array
    {
        static $migrations = [];

        if (empty($migrations)) {
            foreach ($this->migrationsRegister->getMigrations() as $migrationClass) {
                $migrations[$migrationClass::timestamp() . '_' . $migrationClass::id()] = $migrationClass::id();
            }

            ksort($migrations);
        }

        return $migrations;
    }

    /**
     * @since 1.2.0
     */
    public function getRunOrderForMigration(string $migrationId): int
    {
        return array_search($migrationId, array_values($this->getMigrationsSorted())) + 1;
    }
}
