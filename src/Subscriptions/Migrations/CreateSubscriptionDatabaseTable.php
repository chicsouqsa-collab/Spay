<?php

/**
 * A migration which is uses to create database table for subscription.
 *
 * @package StellarPay\Subscription\Migrations
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Migrations;

use Exception;
use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Subscriptions\Repositories\OptionsRepository;
use StellarPay\Vendors\StellarWP\DB\DB;

/**
 * @since 1.0.0
 */
class CreateSubscriptionDatabaseTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.0.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table   = Subscription::getTableName();
        $charset = DB::get_charset_collate();

        // Check if table exists
        if ($this->tableExists($table)) {
            // Table exists, skip creation
            return;
        }

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            first_order_id BIGINT UNSIGNED NOT NULL,
            first_order_item_id BIGINT UNSIGNED,
            period varchar(20) NOT NULL,
            frequency INT NOT NULL,
            status varchar(20) NOT NULL,
            transaction_id VARCHAR(60),
            billing_total INT UNSIGNED,
            billed_count INT UNSIGNED,
            payment_gateway_mode ENUM('live', 'test') NOT NULL,
            created_at DATETIME NOT NULL,
            created_at_gmt DATETIME NOT NULL,
            started_at DATETIME,
            started_at_gmt DATETIME,
            ended_at DATETIME,
            ended_at_gmt DATETIME,
            trial_started_at DATETIME,
            trial_started_at_gmt DATETIME,
            trial_ended_at DATETIME,
            trial_ended_at_gmt DATETIME,
            next_billing_at DATETIME,
            next_billing_at_gmt DATETIME,
            updated_at DATETIME NOT NULL,
            updated_at_gmt DATETIME NOT NULL,
            expired_at DATETIME,
            expired_at_gmt DATETIME,
            canceled_at DATETIME,
            canceled_at_gmt DATETIME,
            suspended_at DATETIME,
            suspended_at_gmt DATETIME,
            source varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset";

        try {
            DB::delta($sql);

            OptionsRepository::setTableVersion((string)self::timestamp());
        } catch (Exception $exception) {
            $errorMessage = sprintf(
                "An error occurred while creating the $table table. Error details: %s",
                $exception->getMessage(),
            );
            throw new DatabaseMigrationException(
                esc_attr($errorMessage),
                0,
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
    }

    /**
     * @since 1.0.0
     */
    public static function timestamp(): int
    {
        return strtotime('2024-09-10 20:11:00');
    }

    /**
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'create-subscription-database-table';
    }

    /**
     * @since 1.2.0
     */
    public static function title(): string
    {
        return esc_html__('Create Subscription Database Table', 'stellarpay');
    }

    /**
     * @since 1.0.0
     */
    public static function source(): string
    {
        return esc_html__('Subscription', 'stellarpay');
    }
}
