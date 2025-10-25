<?php

/**
 * A migration which is uses to create database table for webhook events.
 *
 * @package StellarPay\Webhook\Migrations
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\Migrations;

use Exception;
use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\Migrations\Exceptions\DatabaseMigrationException;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Webhook\Repositories\OptionsRepository;
use StellarPay\Vendors\StellarWP\DB\DB;

use function StellarPay\Core\container;

/**
 * @since 1.1.0
 */
class CreateWebhookEventsTable extends Migration
{
    use DatabaseUtilities;

    /**
     * @since 1.1.0
     * @throws DatabaseMigrationException
     */
    public function run(): void
    {
        $table   = WebhookEvent::getTableName();
        $charset = DB::get_charset_collate();

        // Check if table exists
        if ($this->tableExists($table)) {
            // Table exists, skip creation
            return;
        }

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            payment_gateway_mode ENUM('live', 'test') NOT NULL,
            source_id BIGINT UNSIGNED NOT NULL,
            source_type VARCHAR(255) NOT NULL,
            request_status VARCHAR(255) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            created_at_gmt DATETIME(3) NOT NULL,
            response_time DATETIME(3) NULL,
            response_time_gmt DATETIME(3) NULL,
            notes LONGTEXT NULL,
            PRIMARY KEY (id)
        ) $charset";

        try {
            DB::delta($sql);
            container(OptionsRepository::class)->setWebhookEventTableVersion((string)self::timestamp());
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
     * @since 1.1.0
     */
    public static function timestamp(): int
    {
        return strtotime('2024-09-10 20:51:00');
    }

    /**
     * @since 1.1.0
     */
    public static function id(): string
    {
        return 'create-webhook-event-table';
    }

    /**
     * @since 1.2.0
     */
    public static function title(): string
    {
        return esc_html__('Create Webhook Events Table', 'stellarpay');
    }

    /**
     * @since 1.1.0
     */
    public static function source(): string
    {
        return esc_html__('Core', 'stellarpay');
    }
}
