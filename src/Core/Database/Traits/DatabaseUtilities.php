<?php

/**
 * This trait use to access database related helper functions.
 *
 * @package StellarPay\Core\Database\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Database\Traits;

use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.0.0
 */
trait DatabaseUtilities
{
    /**
     * @since 1.0.0
     */
    protected function tableExists(string $table): bool
    {
        $query = DB::prepare("SHOW TABLES LIKE %s", $table);
        $result = DB::get_var($query);

        return (bool)$result;
    }

    /**
     * @since 1.3.0
     */
    protected function columnExists(string $columnName, string $table): bool
    {
        $query = DB::prepare("SHOW COLUMNS FROM {$table} LIKE %s", $columnName);
        $result = DB::get_var($query);

        return (bool)$result;
    }
}
