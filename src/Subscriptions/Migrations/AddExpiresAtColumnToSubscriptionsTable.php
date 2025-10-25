<?php

/**
 * @package StellarPay\MigrationLog\Migrations
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Migrations;

use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.3.0
 */
class AddExpiresAtColumnToSubscriptionsTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.3.0
     */
    public static function id(): string
    {
        return 'add-expires-at-column-to-subscriptions-table';
    }

    /**
     * @since 1.3.0
     */
    public static function title(): string
    {
        return esc_html__('Add `expires_at` column to Subscriptions table', 'stellarpay');
    }

    /**
     * @since 1.3.0
     */
    public static function timestamp(): int
    {
        return strtotime('2025-01-14 00:00');
    }

    /**
     * @since 1.3.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table = Subscription::getTableName();

        $errorMessage = "An error occurred while creating the `expires_at` column on {$table} table";

        // Check if table exists
        if (!$this->tableExists($table)) {
            throw new DatabaseMigrationException($errorMessage, 0); // phpcs:ignore WordPress.Security.EscapeOutput
        }

        // Check if a column exists
        if ($this->columnExists('expires_at', $table)) {
            return;
        }

        $sql = "ALTER TABLE {$table}
            ADD expires_at DATETIME AFTER updated_at_gmt,
            ADD expires_at_gmt DATETIME AFTER expires_at
        ";

        try {
            DB::query($sql);
        } catch (\Exception $exception) {
            throw new DatabaseMigrationException($errorMessage, 0, $exception); // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }
}
