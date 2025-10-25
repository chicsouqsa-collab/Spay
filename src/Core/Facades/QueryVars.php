<?php

/**
 * This class is a facade for the QueryVars class.
 * 
 * @package StellarPay\Core\Facades
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Facades;

use StellarPay\Core\Support\Facades\Facade;
use StellarPay\Core\QueryVars as QueryVarsClass;

/**
 * QueryVars Facade
 * 
 * Provides a static interface to WordPress query variables.
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array all()
 * @method static bool has(string $key)
 * @method static bool missing(string $key)
 * @method static int getInt(string $key, int $default = 0)
 * @method static string getString(string $key, string $default = '')
 * @method static bool getBool(string $key, bool $default = false)
 * 
 * @since 1.7.0
 */
class QueryVars extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * 
     * @since 1.7.0
     */
    protected function getFacadeAccessor(): string
    {
        return QueryVarsClass::class;
    }
} 
