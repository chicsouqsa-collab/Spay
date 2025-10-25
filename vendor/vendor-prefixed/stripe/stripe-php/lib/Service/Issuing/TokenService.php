<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\Issuing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class TokenService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Lists all Issuing <code>Token</code> objects for a given card.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Collection<\Stripe\Issuing\Token>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/issuing/tokens', $params, $opts);
    }

    /**
     * Retrieves an Issuing <code>Token</code> object.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Token
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/issuing/tokens/%s', $id), $params, $opts);
    }

    /**
     * Attempts to update the specified Issuing <code>Token</code> object to the status
     * specified.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Issuing\Token
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/issuing/tokens/%s', $id), $params, $opts);
    }
}
