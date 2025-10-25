<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class CreditBalanceTransactionService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Retrieve a list of credit balance transactions.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Collection<\Stripe\Billing\CreditBalanceTransaction>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/billing/credit_balance_transactions', $params, $opts);
    }

    /**
     * Retrieves a credit balance transaction.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Billing\CreditBalanceTransaction
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/billing/credit_balance_transactions/%s', $id), $params, $opts);
    }
}
