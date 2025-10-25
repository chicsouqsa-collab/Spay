<?php

/**
 * Api Route abstract.
 *
 * This abstract should be implemented by all API routes.
 *
 * @package StellarPay/RestApi/Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\RestApi\Endpoints;

use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Facades\QueryVars;
use WP_Error;
use WP_REST_Request;

use function StellarPay\Core\container;
use function StellarPay\Core\isRestAPIRequest;

/**
 * Class ApiRoute
 *
 * @since 1.0.0
 */
abstract class ApiRoute implements \StellarPay\RestApi\Contracts\ApiRoute
{
    /**
     * Routes Namespace.
     *
     * This is the namespace for the route without the trailing slash on both sides.
     *
     * @var string $namespace
     */
    protected string $namespace = Constants::PLUGIN_SLUG . '/v1';

    /**
     * Routes Endpoint.
     *
     * This is the endpoint for the route without the trailing slash.
     *
     * @var string $endpoint
     */
    protected string $endpoint = '';

    /**
     * @since 1.4.0
     */
    public function __construct()
    {
        $this->namespace = ltrim(untrailingslashit($this->namespace), '/');
        $this->endpoint = ltrim(untrailingslashit($this->endpoint), '/');
    }

    /**
     * Register REST routes.
     *
     * @since 1.0.0
     */
    abstract public function register(): void;

    /**
     * This function validates the nonce.
     *
     * Note: Developer should extend this function in child class to add more permission checks.
     *
     * @since 1.0.0
     *
     * @return bool|WP_Error
     */
    public function permissionCheck(WP_REST_Request $request)
    {
        // Verify the nonce
        $nonce = $request->get_header('X-WP-Nonce');

        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('stellarpay_rest_forbidden', 'Invalid Access.', ['status' => 403]);
        }

        return true;
    }

    /**
     * This function returns an array of route arguments.
     *
     * Note: Developer should extend this function in child class when adding more routes.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array
    {
        return [];
    }

    /**
     * Get the path of the route.
     *
     * @since 1.0.0
     *
     * @return string|array
     */
    public function getPath()
    {
        if ($routes = $this->getRoutes()) {
            $paths = array_keys($routes);
            $result = [];

            foreach ($paths as $path) {
                $result[$path] = "$this->namespace/$this->endpoint/$path";
            }

            return $result;
        }

        return "$this->namespace/$this->endpoint";
    }

    /**
     * @since 1.8.0
     */
    public function getPathByRouteId(string $routeId = ''): string
    {
        $routes = $this->getPath();

        if (empty($routeId)) {
            return (string)$routes;
        }

        if (is_array($routes) && array_key_exists($routeId, $routes)) {
            return $routes[$routeId];
        }

        return (string)$routes;
    }

    /**
     * Get the namespace of the route.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get the endpoint of the route.
     *
     * @since 1.0.0
     * @throws Exception
     */
    public function getEndpoint(string $pathSuffix = null): string
    {
        if (! $pathSuffix) {
            return "/$this->endpoint";
        }

        if (array_key_exists($pathSuffix, $this->getRoutes())) {
            return "/$this->endpoint/$pathSuffix";
        }

        throw new Exception('Invalid Api Route');
    }

    /**
     * @since 1.0.0
     *
     * @return array|string
     */
    public function getRestApiUrl()
    {
        if ($routes = $this->getRoutes()) {
            $paths = array_keys($routes);
            $result = [];

            foreach ($paths as $path) {
                $result[$path] = rest_url("$this->namespace/$this->endpoint/$path");
            }

            return $result;
        }

        return rest_url($this->getPath());
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    protected static function isRequest(string $routeId = ''): bool
    {
        if (! isRestAPIRequest()) {
            return false;
        }

        $currentRoute = untrailingslashit(QueryVars::get('rest_route') ?? '');
        $expectedRoute = '/' . container(static::class)->getPathByRouteId($routeId);


        return $currentRoute === $expectedRoute;
    }
}
