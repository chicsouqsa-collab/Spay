<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\Forwarding;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class RequestService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Lists all ForwardingRequest objects.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Collection<\Stripe\Forwarding\Request>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/forwarding/requests', $params, $opts);
    }

    /**
     * Creates a ForwardingRequest object.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Forwarding\Request
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/forwarding/requests', $params, $opts);
    }

    /**
     * Retrieves a ForwardingRequest object.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Forwarding\Request
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/forwarding/requests/%s', $id), $params, $opts);
    }
}
