<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\Checkout;

/**
 * Service factory class for API resources in the Checkout namespace.
 *
 * @property SessionService $sessions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class CheckoutServiceFactory extends \StellarPay\Vendors\Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'sessions' => SessionService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
