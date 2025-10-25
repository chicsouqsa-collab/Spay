<?php

/**
 * A migration which is used to add resumed_at and resumed_at_gmt columns to the subscription table.
 *
 * @package StellarPay\Subscription\Migrations
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Migrations;

use Exception;
use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.9.0
 */
class AddResumedAtColumnsToSubscriptionTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.9.0
     */
    public static function id(): string
    {
        return 'add-resumed-at-columns-to-subscription-table';
    }

    /**
     * @since 1.9.0
     */
    public static function timestamp(): int
    {
        return strtotime('2025-05-05 00:00:00');
    }

    /**
     * @since 1.9.0
     */
    public static function title(): string
    {
        return esc_html__('Add resumed_at columns to Subscription Table', 'stellarpay');
    }

    /**
     * @since 1.9.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table = Subscription::getTableName();
        $errorMessage = "An error occurred while creating the `resumed_at` column on {$table} table";

        // Check if columns already exist
        if ($this->columnExists('resumed_at', $table)) {
            return;
        }

        $sql = "ALTER TABLE {$table}
            ADD resumed_at DATETIME AFTER updated_at_gmt,
            ADD resumed_at_gmt DATETIME AFTER resumed_at
        ";

        try {
            DB::query($sql);
        } catch (Exception $exception) {
            throw new DatabaseMigrationException(
                esc_attr($errorMessage),
                0,
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
    }
}
