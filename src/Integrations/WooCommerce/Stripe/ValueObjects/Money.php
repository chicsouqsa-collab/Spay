<?php

/**
 * Money value object.
 *
 * This class is responsible for managing money value object for Woocoomerce Stripe integration.
 *
 * @package StellarPay/Core/ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\ValueObjects;

use StellarPay\Core\ValueObjects\Money as BaseMoney;

/**
 * Class Money
 *
 * @since 1.0.0
 */
class Money extends BaseMoney
{
    /**
     * @since 1.0.0
     */
    public function __construct(float $amount, string $currencyCode)
    {
        parent::__construct($amount, $currencyCode);
    }

    /**
     * @since 1.0.0
     *
     * @return static
     */
    public static function fromMinorAmount(int $minorAmount, string $currencyCode)
    {
        return parent::fromMinorAmount($minorAmount, $currencyCode);
    }
}
