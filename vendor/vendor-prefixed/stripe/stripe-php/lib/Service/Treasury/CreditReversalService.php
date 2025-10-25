<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class CreditReversalService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Returns a list of CreditReversals.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Collection<\Stripe\Treasury\CreditReversal>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/credit_reversals', $params, $opts);
    }

    /**
     * Reverses a ReceivedCredit and creates a CreditReversal object.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Treasury\CreditReversal
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/treasury/credit_reversals', $params, $opts);
    }

    /**
     * Retrieves the details of an existing CreditReversal by passing the unique
     * CreditReversal ID from either the CreditReversal creation request or
     * CreditReversal list.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Treasury\CreditReversal
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/credit_reversals/%s', $id), $params, $opts);
    }
}
