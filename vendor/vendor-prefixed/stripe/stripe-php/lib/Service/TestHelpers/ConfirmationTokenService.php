<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\TestHelpers;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class ConfirmationTokenService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Creates a test mode Confirmation Token server side for your integration tests.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\ConfirmationToken
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/test_helpers/confirmation_tokens', $params, $opts);
    }
}
