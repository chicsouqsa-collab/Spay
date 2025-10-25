<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Events;

/**
 * @property \StellarPay\Vendors\Stripe\RelatedObject $related_object Object containing the reference to API resource relevant to the event
 * @property \StellarPay\Vendors\Stripe\EventData\V1BillingMeterErrorReportTriggeredEventData $data data associated with the event
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class V1BillingMeterErrorReportTriggeredEvent extends \StellarPay\Vendors\Stripe\V2\Event
{
    const LOOKUP_TYPE = 'v1.billing.meter.error_report_triggered';

    /**
     * Retrieves the related object from the API. Make an API request on every call.
     *
     * @throws \StellarPay\Vendors\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarPay\Vendors\Stripe\Billing\Meter
     */
    public function fetchRelatedObject()
    {
        $apiMode = \StellarPay\Vendors\Stripe\Util\Util::getApiMode($this->related_object->url);
        list($object, $options) = $this->_request(
            'get',
            $this->related_object->url,
            [],
            ['stripe_account' => $this->context],
            [],
            $apiMode
        );

        return \StellarPay\Vendors\Stripe\Util\Util::convertToStripeObject($object, $options, $apiMode);
    }

    public static function constructFrom($values, $opts = null, $apiMode = 'v2')
    {
        $evt = parent::constructFrom($values, $opts, $apiMode);
        if (null !== $evt->data) {
            $evt->data = \StellarPay\Vendors\Stripe\EventData\V1BillingMeterErrorReportTriggeredEventData::constructFrom($evt->data, $opts, $apiMode);
        }

        return $evt;
    }
}
