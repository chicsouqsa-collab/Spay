<?php

/**
 * A migration which is uses to create database table for subscription meta.
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
class CreateSubscriptionMetaDatabaseTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.0.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table   = Subscription::getMetaTableName();
        $subscriptionTable = Subscription::getTableName();
        $charset = DB::get_charset_collate();

        // Check if table exists
        if ($this->tableExists($table)) {
            // Table exists, skip creation
            return;
        }

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subscription_id BIGINT UNSIGNED NOT NULL,
            meta_key Varchar(255) NOT NULL,
            meta_value LONGTEXT NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (subscription_id) REFERENCES $subscriptionTable (id)
        ) $charset";

        try {
            DB::delta($sql);

            OptionsRepository::setMetaTableVersion((string)self::timestamp());
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
        return strtotime('2024-09-10 20:41:00');
    }

    /**
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'create-subscription-meta-database-table';
    }

    /**
     * @since 1.2.0
     */
    public static function title(): string
    {
        return esc_html__('Create Subscription Meta Database Table', 'stellarpay');
    }

    /**
     * @since 1.0.0
     */
    public static function source(): string
    {
        return esc_html__('Subscription', 'stellarpay');
    }
}
