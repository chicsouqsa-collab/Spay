<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Events;

/**
 * @property \StellarPay\Vendors\Stripe\EventData\V1BillingMeterNoMeterFoundEventData $data data associated with the event
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class V1BillingMeterNoMeterFoundEvent extends \StellarPay\Vendors\Stripe\V2\Event
{
    const LOOKUP_TYPE = 'v1.billing.meter.no_meter_found';

    public static function constructFrom($values, $opts = null, $apiMode = 'v2')
    {
        $evt = parent::constructFrom($values, $opts, $apiMode);
        if (null !== $evt->data) {
            $evt->data = \StellarPay\Vendors\Stripe\EventData\V1BillingMeterNoMeterFoundEventData::constructFrom($evt->data, $opts, $apiMode);
        }

        return $evt;
    }
}
