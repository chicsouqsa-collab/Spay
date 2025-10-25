<?php

/**
 * Request
 *
 * This class is used to manage the request data.
 * It also provides methods to retrieve, sanitize, and redirect the request.
 *
 * @package StellarPay\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use StellarPay\Vendors\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Class Request
 *
 * @since 1.0.0
 */
class Request
{
    /**
     * This function is used to retrieve data from the request.
     *
     * @param string $key The key to retrieve from the request
     * @param mixed $default The default value to return if the key is not found
     *
     * @since 1.0.0
     * @return mixed Sanitized data from the request
     */
    public function get(string $key, $default = null)
    {
        return SuperGlobals::get_get_var($key, $default);
    }

    /**
     * This function is used to retrieve data from the request.
     *
     * @param string $key The key to retrieve from the request
     * @param mixed $default The default value to return if the key is not found
     *
     * @since 1.0.0
     * @return mixed Sanitized data from the request
     */
    public function post(string $key, $default = null)
    {
        return SuperGlobals::get_post_var($key, $default);
    }

    /**
     * This function is used to retrieve all data from the request.
     *
     * @since 1.0.0
     * @return array Request raw data from the GET and POST super globals
     */
    public function all(): array
    {
        return array_merge(
            SuperGlobals::get_raw_superglobal('GET'),
            SuperGlobals::get_raw_superglobal('POST')
        );
    }

    /**
     * This function is used to check if a key exists in the request.
     *
     * @param string $key The key to check for in the request
     *
     * @since 1.0.0
     */
    public function has(string $key): bool
    {
        $all = $this->all();
        return $all && array_key_exists($key, $all);
    }

    /**
     * This function is used to sanitize data.
     *
     * @param array|string $data The data to sanitize
     *
     * @since 1.0.0
     * @return array|string
     */
    public function sanitize($data)
    {
        // If the data is a string, sanitize it.
        if (! is_array($data)) {
            return sanitizeTextField($data);
        }

        return array_map([ $this, __FUNCTION__], $data);
    }

    /**
     * This function is used to retrieve the request body.
     *
     * @since 1.0.0
     */
    public function getBody(): string
    {
        $function = 'file_get_contents';

        if (function_exists('wpcom_vip_file_get_contents')) {
            $function = 'wpcom_vip_file_get_contents';
        }

        return $function('php://input');
    }

    /**
     * This function is used to check if the request has a valid nonce.
     *
     * @param string $action The action to check the nonce for
     *
     * @since 1.0.0
     */
    public function hasValidNonce(string $action): bool
    {
        $nonceName = Constants::NONCE_NAME;
        $requestedData = $this->all();
        return array_key_exists($nonceName, $requestedData)
            && wp_verify_nonce($requestedData[$nonceName], $action);
    }

    /**
     * This function is used to check if the request has a valid capability.
     *
     * @param string $capability The ability to check for
     *
     * @since 1.0.0
     */
    public function hasPermission(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * This function is used to check if the request method is valid.
     *
     * @since 1.0.0
     */
    public function usesHttpMethod(string $type): bool
    {
        $server = SuperGlobals::get_raw_superglobal('SERVER');
        return isset($server['REQUEST_METHOD'])
              && ( strtoupper($type) === $server['REQUEST_METHOD'] );
    }

    /**
     * This function is used to check if the request uses the GET method.
     *
     * @since 1.0.0
     */
    public function usesGetMethod(): bool
    {
        return $this->usesHttpMethod('GET');
    }

    /**
     * This function is used to check if the request uses the POST method.
     *
     * @since 1.0.0
     */
    public function usesPostMethod(): bool
    {
        return $this->usesHttpMethod('POST');
    }
}
