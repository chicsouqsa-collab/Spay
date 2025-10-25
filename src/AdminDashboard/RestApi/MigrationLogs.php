<?php

/**
 * @package StellarPay\AdminDashboard\RestApi
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\Core\Migrations\Helpers\MigrationHelpers;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\Support\Str;
use StellarPay\MigrationLog\MigrationLogModel;
use StellarPay\MigrationLog\MigrationLogRepository;
use StellarPay\MigrationLog\MigrationLogStatus;
use StellarPay\RestApi\HandleMultipleApiRoutes;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @since 1.2.0
 *
 */
class MigrationLogs extends ApiRoute
{
    use HandleMultipleApiRoutes {
        RegisterAllRoutes as register;
    }

    use MigrationHelpers;

    /**
     * @since 1.2.0
     */
    protected MigrationLogRepository $migrationLogRepository;

    /**
     * @since 1.2.0
     */
    protected MigrationsRegister $migrationsRegister;

    /**
     * @since 1.2.0
     */
    public function __construct(MigrationLogRepository $migrationLogRepository, MigrationsRegister $migrationsRegister)
    {
        parent::__construct();

        $this->migrationLogRepository = $migrationLogRepository;
        $this->migrationsRegister = $migrationsRegister;
    }

    /**
     * @inheritdoc
     * @since 1.2.0
     */
    protected string $endpoint = 'migration-logs';

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array
    {
        return [
            'list' => [
                'method' => WP_REST_Server::READABLE,
                'args' => [
                    'pageNumber' => [
                        'required' => true,
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                        'sanitize_callback' => 'absint'
                    ]
                ],
            ]
        ];
    }

    /**
     * @since 1.2.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) && current_user_can('manage_options');
    }

    /**
     * @since 1.3.0 Handle fatal error when trying to fetch missing migration
     * @since 1.2.0
     */
    protected function getList(\WP_REST_Request $request): WP_REST_Response
    {
        $pageNumber = $request->get_param('pageNumber');
        $perPage = get_option('posts_per_page', 15);
        $migrationLogs = $this->migrationLogRepository->getAll(['page' => $pageNumber, 'perPage' => $perPage]);

        $result['page'] = $pageNumber;
        $result['perPage'] = $perPage;
        $result['total'] = MigrationLogModel::totalCount();

        $result['migrationLogs'] = [];

        foreach ($migrationLogs as $migrationLog) {
            try {
                $migrationClass = $this->migrationsRegister->getMigration($migrationLog->id);

                $data = [
                    'id' => $migrationLog->id,
                    'title' => $migrationClass::title(),
                    'status' => $migrationLog->status,
                    'lastRun' => Temporal::getWPFormattedDate($migrationLog->lastRun),
                    'order' => $this->getRunOrderForMigration($migrationLog->id)
                ];
            } catch (\Exception $exception) {
                $data = [
                    'id' => $migrationLog->id,
                    'title' => Str::headline($migrationLog->id),
                    'status' => MigrationLogStatus::MISSING,
                    'lastRun' => Temporal::getWPFormattedDate($migrationLog->lastRun),
                    'order' => ''
                ];
            }

            $result['migrationLogs'][] = $data;
        }

        return rest_ensure_response($result);
    }
}
