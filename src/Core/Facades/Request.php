<?php

/**
 * This class is a facade for the Request class.
 * 
 * @package StellarPay\Core\Facades
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Facades;

use StellarPay\Core\Request as RequestClass;
use StellarPay\Core\Support\Facades\Facade;

/**
 * Request Facade
 * 
 * Provides a static interface to the Request functionality.
 * This facade makes it easier to work with HTTP requests throughout the application.
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed post(string $key, mixed $default = null)
 * @method static array all()
 * @method static bool has(string $key)
 * @method static mixed sanitize(mixed $data)
 * @method static string getBody()
 * @method static bool hasValidNonce(string $action)
 * @method static bool hasPermission(string $capability)
 * @method static bool usesHttpMethod(string $type)
 * @method static bool usesGetMethod()
 * @method static bool usesPostMethod()
 * 
 * @since 1.7.0
 */
class Request extends Facade
{
    /**
     * @since 1.7.0
     */
    protected function getFacadeAccessor(): string
    {
        return RequestClass::class;
    }
} 
