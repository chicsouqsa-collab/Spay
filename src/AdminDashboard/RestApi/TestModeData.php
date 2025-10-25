<?php

/**
 * Test Mode Data API registration class.
 *
 * @package StellarPay
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\AdminDashboard\TestModeDataDeletionRuleRunner;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\ActionScheduler\ActionScheduler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class TestModeDataAPI
 *
 * @since 1.2.0
 */
class TestModeData extends ApiRoute
{
    /**
     * @since 1.2.0
     */
    protected string $endpoint = 'test-mode-data';

    /**
     * @since 1.2.0
     */
    protected TestModeDataDeletionRuleRunner $testModeDataDeletionRuleRunner;

    /**
     * @since 1.2.0
     */
    public function __construct(TestModeDataDeletionRuleRunner $testModeDataDeletionRuleRunner)
    {
        parent::__construct();

        $this->testModeDataDeletionRuleRunner = $testModeDataDeletionRuleRunner;
    }

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.2.0
     */
    public function getRoutes(): array
    {
        return [
            'delete' => [
                'method' => WP_REST_Server::CREATABLE,
                'callback' => 'deleteTestModeData',
            ],
            'delete-status' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'getDeleteStatus',
            ],
        ];
    }

    /**
     * Register REST routes.
     *
     * @since 1.2.0
     * @throws Exception
     */
    public function register(): void
    {
        $routes = $this->getRoutes();

        foreach ($routes as $route => $details) {
            register_rest_route(
                $this->getNamespace(),
                $this->getEndpoint($route),
                [
                    'methods' => $details['method'],
                    'callback' => [$this, $details['callback']],
                    'permission_callback' => [$this, 'permissionCheck'],
                    'args' => [],
                ]
            );
        }
    }

    /**
     * @inheritDoc
     *
     * @since 1.2.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) &&
            current_user_can('manage_options');
    }

    /**
     * @since 1.2.0
     *
     * @return WP_Error|WP_REST_Response
     */
    public function deleteTestModeData()
    {
        try {
            $this->testModeDataDeletionRuleRunner->scheduleJob();
        } catch (\Exception $exception) {
            return new WP_Error('test_mode_data_error', esc_html__('Failed to delete test mode data', 'stellarpay'));
        }

        return new WP_REST_Response(
            ['message' => esc_html__('Deletion in progress. This may take a minute..', 'stellarpay'),],
            200
        );
    }

    /**
     * @since 1.2.0
     */
    public function getDeleteStatus()
    {
        if (ActionScheduler::hasScheduledAction(TestModeDataDeletionRuleRunner::getActionSchedulerJobName())) {
            return rest_ensure_response('in-progress');
        }

        return rest_ensure_response('finished');
    }
}
