<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Stripe;

/**
 * @property string $id Unique identifier for the event.
 * @property string $type
 * @property string $url
 */
class RelatedObject
{
    public $id;
    public $type;
    public $url;
}
