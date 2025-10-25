<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Stripe\V2;

/**
 * @property string $id Unique identifier for the event.
 * @property string $object String representing the object's type. Objects of the same type share the same value of the object field.
 * @property int $created Time at which the object was created.
 * @property \StellarPay\Vendors\Stripe\StripeObject $reason Reason for the event.
 * @property string $type The type of the event.
 * @property null|string $context The Stripe account of the event
 */
abstract class Event extends \StellarPay\Vendors\Stripe\ApiResource
{
    const OBJECT_NAME = 'v2.core.event';
}
