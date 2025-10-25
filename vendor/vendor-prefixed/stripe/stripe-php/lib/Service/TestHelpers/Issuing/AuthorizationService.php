<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\TestHelpers\Issuing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class AuthorizationService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Capture a test-mode authorization.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function capture($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/authorizations/%s/capture', $id), $params, $opts);
    }

    /**
     * Create a test-mode authorization.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/test_helpers/issuing/authorizations', $params, $opts);
    }

    /**
     * Expire a test-mode Authorization.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function expire($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/authorizations/%s/expire', $id), $params, $opts);
    }

    /**
     * Finalize the amount on an Authorization prior to capture, when the initial
     * authorization was for an estimated amount.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function finalizeAmount($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/authorizations/%s/finalize_amount', $id), $params, $opts);
    }

    /**
     * Increment a test-mode Authorization.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function increment($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/authorizations/%s/increment', $id), $params, $opts);
    }

    /**
     * Reverse a test-mode Authorization.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Authorization
     */
    public function reverse($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/test_helpers/issuing/authorizations/%s/reverse', $id), $params, $opts);
    }
}
