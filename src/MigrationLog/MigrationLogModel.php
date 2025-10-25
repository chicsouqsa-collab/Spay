<?php

/**
 * @package StellarPay\MigrationLog\Models
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use DateTimeInterface;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\Models\Model;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;

use function StellarPay\Core\container;

/**
 * @since 1.2.0
 *
 * @property string $id
 * @property MigrationLogStatus $status
 * @property string $error
 * @property DateTimeInterface $lastRun
 */
class MigrationLogModel extends Model
{
    /**
     * @since 1.2.0
     */
    protected $properties = [
        'id' => 'string',
        'status' => MigrationLogStatus::class,
        'error' => 'string',
        'lastRun' => DateTimeInterface::class
    ];

    /**
     * @since 1.2.0
     */
    public static function getTableName(): string
    {
        return DB::prefix(self::getTableNameWithoutPrefix());
    }

    /**
     * @since 1.2.0
     * @return string
     */
    public static function getTableNameWithoutPrefix(): string
    {
        return Constants::slugPrefixed('_migration_logs');
    }

    /**
     * @since 1.2.0
     */
    protected function getPropertyDefaults(): array
    {
        return [
            'status' => MigrationLogStatus::FAILED()
        ];
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public static function upsert(array $attributes = []): self
    {
        $model = new static($attributes);
        return  container(MigrationLogRepository::class)->upsert($model);
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function delete(): bool
    {
        return container(MigrationLogRepository::class)->delete($this);
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public static function query(): ModelQueryBuilder
    {
        return container(MigrationLogRepository::class)->prepareQuery();
    }

    /**
     * @since 1.2.0
     */
    public static function fromQueryBuilderObject(object $object): self
    {
        return MigrationLogQueryData::fromObject($object)->toModel();
    }

    /**
     * @since 1.0.0
     */
    public static function factory(): MigrationLogFactory
    {
        return new MigrationLogFactory(static::class);
    }

    /**
     * @since 1.2.0
     */
    public static function totalCount(): int
    {
        $count = self::query()->count('id');

        return $count ?: 0;
    }
}
