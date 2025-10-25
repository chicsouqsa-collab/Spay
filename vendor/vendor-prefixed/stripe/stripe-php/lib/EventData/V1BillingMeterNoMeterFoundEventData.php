<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\EventData;

/**
 * @property string $developer_message_summary Extra field included in the event's <code>data</code> when fetched from /v2/events.
 * @property \StellarPay\Vendors\Stripe\StripeObject $reason This contains information about why meter error happens.
 * @property int $validation_end The end of the window that is encapsulated by this summary.
 * @property int $validation_start The start of the window that is encapsulated by this summary.
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class V1BillingMeterNoMeterFoundEventData extends \StellarPay\Vendors\Stripe\StripeObject
{
}
