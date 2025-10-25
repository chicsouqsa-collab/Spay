<?php

/**
 * This class uses to access data from WordPress query variables.
 *
 * @package StellarPay\Core
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use WP;

/**
 * Class QueryVars
 *
 * Handles access to WordPress query variables in a clean way.
 *
 * @package StellarPay\Core
 * @since 1.7.0
 */
class QueryVars
{
    /**
     * WordPress global object.
     *
     * @since 1.7.0
     */
    protected WP $wp;

    /**
     * Constructor.
     *
     * @since 1.7.0
     */
    public function __construct()
    {
        global $wp;
        $this->wp = $wp;
    }

    /**
     * Get a query var value.
     *
     * @return mixed
     *
     * @since 1.7.0
     */
    public function get(string $key, $default = null)
    {
        if (empty($this->wp->query_vars[$key])) {
            return $default;
        }

        return $this->wp->query_vars[$key];
    }

    /**
     * Get all query vars.
     *
     * @since 1.7.0
     */
    public function all(): array
    {
        return $this->wp->query_vars ?? [];
    }

    /**
     * Check if a query var exists.
     *
     * @since 1.9.1 Use "isset" sineatd of "empty"
     * @since 1.7.0
     */
    public function has(string $key): bool
    {
        return isset($this->wp->query_vars[$key]);
    }

    /**
     * Check if a query var does not exist.
     *
     * @since 1.7.0
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Get an integer value from query vars.
     *
     * @since 1.7.0
     */
    public function getInt(string $key, int $default = 0): int
    {
        return absint($this->get($key, $default));
    }

    /**
     * Get a string value from query vars.
     *
     * @since 1.7.0
     */
    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    /**
     * Get a boolean value from query vars.
     *
     * @since 1.7.0
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }
}
