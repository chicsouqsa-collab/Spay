<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class CustomerSessionService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Creates a Customer Session object that includes a single-use client secret that
     * you can use on your front-end to grant client-side API access for certain
     * customer resources.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\CustomerSession
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/customer_sessions', $params, $opts);
    }
}
