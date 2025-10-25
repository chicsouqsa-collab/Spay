<?php

/**
 * @package StellarPay\Core\Migrations
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Migrations;

use StellarPay\Core\Database\Traits\DatabaseUtilities;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\MigrationLog\MigrationLogModel;
use StellarPay\MigrationLog\MigrationLogRepository;
use StellarPay\MigrationLog\MigrationLogStatus;
use StellarPay\Vendors\StellarWP\AdminNotices\AdminNotices;
use StellarPay\Vendors\StellarWP\DB\DB;

use function StellarPay\Core\container;
use function StellarPay\Core\prefixedKey;

/**
 * Class MigrationsRunner
 *
 * @since 1.2.0
 * @template T of Migration
 */
class MigrationsRunner
{
    use DatabaseUtilities;

    /**
     * List of completed migrations.
     *
     * @since 1.2.0
     */
    private array $completedMigrations;

    /**
     * @since 1.2.0
     */
    private MigrationsRegister $migrationRegister;


    /**
     * @since 1.2.0
     */
    private MigrationLogRepository $migrationLogRepository;

    /**
     * @since 1.2.0
     */
    private bool $migrationLogTableExits;

    /**
     *  MigrationsRunner constructor.
     *
     * @param MigrationsRegister $migrationRegister
     * @param MigrationLogRepository $migrationLogRepository
     */
    public function __construct(
        MigrationsRegister $migrationRegister,
        MigrationLogRepository $migrationLogRepository
    ) {
        $this->migrationRegister = $migrationRegister;
        $this->migrationLogRepository = $migrationLogRepository;
        $this->migrationLogTableExits = $this->tableExists(MigrationLogModel::getTableName());
        $this->completedMigrations = $this->migrationLogTableExits ? $this->migrationLogRepository->getCompletedMigrationsIds() : [];
    }

    /**
     * Run database migrations.
     *
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function __invoke()
    {
        if (! $this->migrationLogTableExits) {
            return;
        }

        if (! $this->hasMigrationToRun()) {
            return;
        }

        if ($this->hasFailedMigrations()) {
            return;
        }

        // Store and sort migrations by timestamp
        /* @var array<string,class-string<T>>[] $migrations Array of migrations */
        $migrations = [];

        foreach ($this->migrationRegister->getMigrations() as $migrationClass) {
            $migrations[$migrationClass::timestamp() . '_' . $migrationClass::id()] = $migrationClass;
        }

        ksort($migrations);

        foreach ($migrations as $migrationClass) {
            $migrationId = $migrationClass::id();

            if ($this->isMigrationCompleted($migrationId)) {
                continue;
            }

            $migrationLogArgs = [];

            // Begin transaction
            DB::beginTransaction();

            try {
                $migration = container($migrationClass);

                $migrationLogArgs['id'] = $migrationId;
                $migrationLogArgs['status'] = MigrationLogStatus::SUCCESS();

                $migration->run();
            } catch (\Exception $exception) {
                DB::rollBack();

                $migrationLogArgs['status'] = MigrationLogStatus::FAILED();
                $migrationLogArgs['error'] = print_r($exception, true); // phpcs:ignore

                $this->registerAdminNotice();
            }

            $migrationLog = MigrationLogModel::upsert($migrationLogArgs);

            // Stop Migration Runner if migration has failed
            if ($migrationLog->status->isFailed()) {
                break;
            }

            // Commit transaction if successful
            DB::commit();
        }
    }

    /**
     * Return whether all migrations completed.
     *
     * @since 1.2.0
     */
    private function hasMigrationToRun(): bool
    {
        return (bool)array_diff(
            $this->migrationRegister->getRegisteredIds(),
            $this->completedMigrations
        );
    }

    /**
     * @since 1.2.0
     */
    private function hasFailedMigrations(): bool
    {
        $registerMigrationIds = $this->migrationRegister->getRegisteredIds();
        $failedMigrationIds = $this->migrationLogRepository->getFailedMigrationsCountIds();

        return !empty(array_intersect($registerMigrationIds, $failedMigrationIds));
    }

    /**
     * @since 1.2.0
     */
    private function isMigrationCompleted(string $migrationId): bool
    {
        return in_array($migrationId, $this->completedMigrations, true);
    }

    /**
     * @since 1.2.0
     */
    private function registerAdminNotice(): void
    {
        $noticeId = prefixedKey('migration-failure');
        $notice = AdminNotices::show(
            $noticeId,
            sprintf(
                '%1$s <a href="https://givewp.com/support/">https://links.stellarwp.com/stellarpay/support</a>',
                esc_html__(
                    'There was a problem running the migrations. Please reach out to StellarPay support for assistance:',
                    'stellarpay'
                )
            )
        );

        $notice
            ->asError()
            ->autoParagraph()
            ->ifUserCan('manage_options');
    }
}
