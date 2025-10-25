<?php

/**
 * @package StellarPay\MigrationLog
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;

/**
 * Class MigrationLogRepository
 *
 * @package Give\MigrationLog
 *
 * @since 1.2.0
 */
class MigrationLogRepository
{
    /**
     * @since 1.2.0
     */
    public function upsert(MigrationLogModel $model): MigrationLogModel
    {
        $lastRunDate = Temporal::getCurrentDateTime();

        $this->prepareQuery()->upsert(
            [
                'id' => $model->id,
                'status' => $model->status->getValue(),
                'last_run' => Temporal::getFormattedDateTime($lastRunDate),
                'error' => $model->error ?? '',
            ],
            ['id']
        );

        return $model;
    }

    /**
     * @since 1.2.0
     *
     * @return MigrationLogModel[]|array
     */
    public function getAll(array $args = []): ?array
    {
        $defaults = ['page' => 1];
        $args = array_merge($defaults, $args);
        $pageNumber = (int) $args['page'];

        $query = $this->prepareQuery();

        // Set offset.
        if (array_key_exists('perPage', $args) && $args['perPage']) {
            $limit = (int) $args['perPage'];
            $offset = $limit * ($pageNumber - 1);

            $query->offset($offset);
            $query->limit($limit);
        }

        return $query->getAll();
    }

    /**
     * @since 1.2.0
     */
    public function getCompletedMigrationsIds(): array
    {
         return DB::get_col(
             $this->prepareQuery()
                ->select('id')
                ->where('status', MigrationLogStatus::SUCCESS)
                ->getSQL(),
         );
    }

    /**
     * @since 1.2.0
     */
    public function getFailedMigrationsCountIds(): array
    {
        return DB::get_col(
            $this->prepareQuery()
                ->select('id')
                ->where('status', MigrationLogStatus::FAILED)
                ->getSQL()
        );
    }

    /**
     * @since 1.2.0
     */
    public function delete(MigrationLogModel $model): bool
    {
        return $this->prepareQuery()
            ->where('id', $model->id)
            ->delete();
    }

    /**
     * @since 1.2.0
     *
     * @return ModelQueryBuilder<MigrationLogModel>
     */
    public function prepareQuery(): ModelQueryBuilder
    {
        $builder = new ModelQueryBuilder(MigrationLogModel::class);

        return $builder->from(MigrationLogModel::getTableNameWithoutPrefix());
    }
}
