<?php

/**
 * @package StellarPay\MigrationLog\Migrations
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog\Migrations;

use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\MigrationLog\MigrationLogModel;
use StellarPay\Vendors\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.2.0
 */
class CreateMigrationLogTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.2.0
     */
    public static function id(): string
    {
        return 'create-migrations-table';
    }

    /**
     * @since 1.2.0
     */
    public static function title(): string
    {
        return esc_html__('Create Migration Logs Table', 'stellarpay');
    }

    /**
     * @since 1.2.0
     */
    public static function timestamp(): int
    {
        /**
         * For this migration, we have to use the earliest possible date because we will be using
         * the table created with this migration to store the status of the migration
         */
        return strtotime('1970-01-01 00:00');
    }

    /**
     * @since1.2.0
     * @throws DatabaseMigrationException
     */
    public function run()
    {
        $table = MigrationLogModel::getTableName();

        // Check if table exists
        if ($this->tableExists($table)) {
            // Table exists, skip creation
            return;
        }

        $charset = DB::get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id VARCHAR(180) NOT NULL,
            status VARCHAR(16) NOT NULL,
            error text NULL,
            last_run DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset}";

        try {
            DB::delta($sql);
        } catch (DatabaseQueryException $exception) {
            $errorMessage = "An error occurred while creating the {$table} table";
            throw new DatabaseMigrationException($errorMessage, 0, $exception); //phpcs:ignore
        }
    }
}
