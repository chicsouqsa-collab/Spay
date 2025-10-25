<?php

/**
 *
 * @package StellarPay\MigrationLog
 * @since 1.2.0
 *
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use StellarPay\Core\Support\Facades\DateTime\Temporal;

/**
 * @since 1.2.0
 */
class MigrationLogQueryData
{
    /**
     * @since 1.2.0
     */
    public string $id;

    /**
     * @since 1.2.0
     */
    public MigrationLogStatus $status;

    /**
     * @since 1.2.0
     */
    public \DateTimeInterface $lastRun;

    /**
     * @since 1.2.0
     */
    public string $error;

    /**
     * @since 1.2.0
     */
    public static function fromObject(object $migrationLogQueryObject): self
    {
        $self = new self();

        $self->id = $migrationLogQueryObject->id;
        $self->status = MigrationLogStatus::from($migrationLogQueryObject->status);
        $self->lastRun = Temporal::toDateTime($migrationLogQueryObject->last_run);
        $self->error = $migrationLogQueryObject->error ?? '';

        return $self;
    }

    /**
     * Convert DTO to WebhookEvent model.
     *
     * @since 1.2.0
     */
    public function toModel(): MigrationLogModel
    {
        $attributes = get_object_vars($this);

        return new MigrationLogModel($attributes);
    }
}
