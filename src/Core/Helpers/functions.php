<?php

/**
 * This file contains helper functions.
 *
 * @package StellarPay/Core/HelperFunctions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Helpers\Exceptions\IsWebsiteOnlineException;
use WP_Error;

/*
 * ==================================================
 * Service Container Helper Functions
 *
 * This section contains helper functions for working with the service container.
 * ==================================================
 */

/**
 * This function is used to retrieve data from the service container.
 *
 * @since 1.0.0
 *
 * @template T of class-string<T>|string
 *
 * @param T|null $abstract Selector for data to retrieve from the service container
 *
 * @return Container|T|mixed
 * @throws BindingResolutionException
 */
function container(?string $abstract = null)
{
    static $instance = null;

    $instance = $instance ?? new Container();

    if (null !== $abstract) {
        return $instance->make($abstract);
    }

    return $instance;
}

/*
 * ==================================================
 * Data Sanitization Helper Functions
 *
 * This section contains helper functions for sanitizing data.
 * ==================================================
 */

/**
 * This function is used to sanitize data.
 *
 * @since 1.0.0
 *
 * @param string $data The URL to sanitize
 *
 * @return string
 */
function sanitizeTextField(string $data): string
{
    return sanitize_text_field(wp_unslash($data));
}

/*
 * ==================================================
 * URl Helper Functions
 *
 * This section contains helper functions for url.
 * ==================================================
 */

/**
 * This function is used to generate a nonce URL.
 *
 * Note: This function is used to generate a nonce URL from a given plain URL.
 *
 * @since 1.0.0
 *
 * @param string $action The action to perform
 * @param string $url The URL to generate a nonce for
 *
 * @return string The generated nonce URL
 */
function getNonceUrl(string $action, string $url): string
{
    return add_query_arg(
        [ Constants::NONCE_NAME => wp_create_nonce($action)],
        $url
    );
}

/**
 * This function is used to generate a nonce action name.
 *
 * @since 1.0.0
 *
 * @param string $action The action to generate a nonce action name for
 *
 * @return string The generated nonce action name
 */
function getNonceActionName(string $action): string
{
    return Constants::PLUGIN_SLUG . "-$action";
}

/*
 * ==================================================
 * Database Helper Functions
 *
 * This section contains helper functions for working with the database.
 * ==================================================
 */


/**
 * This function is used to generate a meta key.
 *
 * This function helps to generate consistent meta keys for the plugin.
 *
 * @param string $key The key to generate a meta key for
 * @param bool $hide Whether to hide the key or not
 *
 * @since 1.0.0
 *
 * @return string The generated meta key
 */
function dbMetaKeyGenerator(string $key, bool $hide = false): string
{
    $prefix = Constants::PLUGIN_SLUG;
    $key = $prefix . "_$key";

    return $hide ? "_$key" : $key;
}

/**
 * This function is used to generate an option key.
 *
 * This function helps to generate consistent meta keys for the plugin.
 *
 * @param string $key The key to generate a meta key for
 *
 * @since 1.0.0
 *
 * @return string The generated meta key
 */
function dbOptionKeyGenerator(string $key): string
{
    $prefix = Constants::PLUGIN_SLUG;
    return $prefix . "_$key";
}

/**
 * This function returns a string prefixed with plugin slug.
 *
 * We use it to generate unique id.
 *
 * @since 1.0.1
 */
function prefixedKey(string $key): string
{
    return Constants::PLUGIN_SLUG . '-' . $key;
}

/*
 * ==================================================
 * HTTP Request Helper Functions
 *
 * This section contains helper functions for working with the http requests.
 * ==================================================
 */

/**
 * This function is used to make a remote GET request.
 *
 * This function internally decides which function to use based on the availability of the function.
 * If the function is available, it uses `vip_safe_wp_remote_get` function.
 * Otherwise, it falls back to using `wp_remote_get` function.
 *
 * @param string $url The URL to make the GET request to
 * @param array $args Optional arguments to pass along with the request
 *
 * @since 1.0.0
 *
 * @return array|WP_Error The response from the remote GET request
 * @throws Exception
 */
function remote_get(string $url, array $args = [])
{
    $fn = 'wp_remote_get';
    $vipFn = 'vip_safe_wp_remote_get';

    if (function_exists($vipFn)) {
        $fn = $vipFn;
    }

    // Disable ssl verification for local websites unless configured.
    if (! isWebsiteOnline() && ! array_key_exists('sslverify', $args)) {
        $args['sslverify'] = false;
    }

    return $fn($url, $args);
}

/**
 * This function returns whether a website is online
 *
 * @since 1.0.0
 * @throws Exceptions\Primitives\Exception
 */
function isWebsiteOnline(): bool
{
    static $result = null;

    if (! $result) {
        // Set a custom error handler.
        // We use this custom exception to compare a WordPress issue.
        // Read more at the exception file description.
        set_error_handler( // phpcs:ignore
            static function ($level, $message) {
                throw new IsWebsiteOnlineException(esc_html($message));
            }
        );

        try {
            // Checks if URL is available by checking the HTTP response code.
            // The "@" symbol disables warning to prevent logging of error.
            // You will be able to see these notices using a development plugin like Query Monitor.
            $headers = @get_headers(home_url()); // phpcs:ignore

            $result = ! (! $headers || 'HTTP/1.1 404 Not Found' === $headers[0]);
        } catch (IsWebsiteOnlineException $exception) {
            $result = false;
        }

        restore_error_handler();
    }

    return $result;
}

/*
 * ==================================================
 * Section: REST api function
 * Description: This section contains helper functions for working with the REST api.
 * ==================================================
 */


/**
 * @since 1.8.0
 */
function isRestAPIRequest(): bool
{
    return defined('REST_REQUEST') && REST_REQUEST;
}
