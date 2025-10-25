<?php

/**
 * This class is a contract for plugin admin dashboard rest api endpoints.
 *
 * @package StellarPay\AdminDashboard\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\Core\Constants;

/**
 * @since 1.0.0
 */
abstract class ApiRoute extends \StellarPay\RestApi\Endpoints\ApiRoute
{
    /**
     * Routes Namespace.
     *
     * This is the namespace for the route without the trailing slash on both sides.
     *
     * @var string $namespace
     */
    protected string $namespace = Constants::PLUGIN_SLUG . '/admin-dashboard/v1';
}
