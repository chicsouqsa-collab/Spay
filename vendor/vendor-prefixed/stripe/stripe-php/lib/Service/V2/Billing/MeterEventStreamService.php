<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Service\V2\Billing;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class MeterEventStreamService extends \StellarPay\Vendors\Stripe\Service\AbstractService
{
    /**
     * Creates meter events. Events are processed asynchronously, including validation.
     * Requires a meter event session for authentication. Supports up to 10,000
     * requests per second in livemode. For even higher rate-limits, contact sales.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarPay\Vendors\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\TemporarySessionExpiredException
     *
     * @return void
     */
    public function create($params = null, $opts = null)
    {
        $opts = \StellarPay\Vendors\Stripe\Util\RequestOptions::parse($opts);
        if (!isset($opts->apiBase)) {
            $opts->apiBase = $this->getClient()->getMeterEventsBase();
        }
        $this->request('post', '/v2/billing/meter_event_stream', $params, $opts);
    }
}
