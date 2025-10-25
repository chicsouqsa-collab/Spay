<?php

/**
 * This migration is responsible to add amount column to the subscription database table.
 *
 * @package StellarPay\MigrationLog\Migrations
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Migrations;

use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.8.0
 */
class AddAmountColumnToSubscriptionDatabaseTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.8.0
     */
    public static function id(): string
    {
        return 'add-amount-column-to-subscriptions-table';
    }

    /**
     * @since 1.8.0
     */
    public static function title(): string
    {
        return esc_html__('Add `initial_amount`, `recurring_amount`, `currency_code` columns to Subscriptions table', 'stellarpay');
    }

    /**
     * @since 1.8.0
     */
    public static function timestamp(): int
    {
        return strtotime('2023-09-15 00:00');
    }

    /**
     * @since 1.8.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table = Subscription::getTableName();

        $errorMessage = "An error occurred while creating the `initial_amount`, `recurring_amount`, `currency_code` columns on {$table} table";

        // Check if table exists
        if (!$this->tableExists($table)) {
            throw new DatabaseMigrationException($errorMessage, 0); // phpcs:ignore WordPress.Security.EscapeOutput
        }

        // Check if a column exists
        if ($this->columnExists('initial_amount', $table)) {
            return;
        }

        $sql = "ALTER TABLE {$table}
            ADD initial_amount BIGINT UNSIGNED NULL AFTER billed_count,
            ADD recurring_amount BIGINT UNSIGNED NULL AFTER initial_amount,
            ADD currency_code VARCHAR(3) NULL AFTER recurring_amount
        ";

        try {
            DB::query($sql);
        } catch (\Exception $exception) {
            throw new DatabaseMigrationException($errorMessage, 0, $exception); // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }
}
