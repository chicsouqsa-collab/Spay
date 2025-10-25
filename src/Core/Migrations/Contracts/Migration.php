<?php

/**
 * Extend this class when create database migration. up and timestamp are required member functions
 *
 * @package StellarPay\Core\Migrations\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Migrations\Contracts;

use StellarPay\Core\Exceptions\Primitives\RuntimeException;

/**
 * @since 1.0.0
 */
abstract class Migration
{
    /**
     * Bootstrap migration logic.
     *
     * @since 1.0.0
     */
    abstract public function run();

    /**
     * Return a unique identifier for the migration
     *
     * @since 1.0.0
     */
    public static function id(): string
    {
        throw new RuntimeException('A unique ID must be provided for the migration');
    }

    /**
     * Return a Unix Timestamp for when the migration was created
     *
     * Example: strtotime('2020-09-16 12:30:00')
     *
     * @since 1.0.0
     */
    public static function timestamp(): int
    {
        throw new RuntimeException('This method must be overridden to return a valid unix timestamp');
    }

    /**
     * Return migration title
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function title(): string
    {
        return static::id();
    }

    /**
     * Return migration source
     *
     * @since 1.0.0
     */
    public static function source(): string
    {
        return esc_html__('Core', 'stellarpay');
    }
}
