<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Stripe\Util;

class EventTypes
{
    const thinEventMapping = [
        // The beginning of the section generated from our OpenAPI spec
        \StellarPay\Vendors\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::LOOKUP_TYPE => \StellarPay\Vendors\Stripe\Events\V1BillingMeterErrorReportTriggeredEvent::class,
        \StellarPay\Vendors\Stripe\Events\V1BillingMeterNoMeterFoundEvent::LOOKUP_TYPE => \StellarPay\Vendors\Stripe\Events\V1BillingMeterNoMeterFoundEvent::class,
        // The end of the section generated from our OpenAPI spec
    ];
}
