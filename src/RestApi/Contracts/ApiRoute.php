<?php

/**
 * Api Route interface.
 *
 * This interface should be implemented by all API routes.
 *
 * @package StellarPay/RestApi/Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\RestApi\Contracts;

use WP_Error;
use WP_REST_Request;

/**
 * Interface ApiRoute
 *
 * @since 1.0.0
 */
interface ApiRoute
{
    /**
     * This function returns whether the route is accessible to a specific context.
     *
     * @since 1.0.0
     *
     * @return bool|WP_Error
     */
    public function permissionCheck(WP_REST_Request $request);

    /**
     * This function registers the REST routes.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array;

    /**
     * This function returns the path of the route.
     *
     * @since 1.0.0
     *
     * @return string|array The route path. If the route is an array, returns paths.
     */
    public function getPath();
}
