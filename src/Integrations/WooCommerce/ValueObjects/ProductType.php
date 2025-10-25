<?php

/**
 *
 * This class is responsible for providing enum for the woocommerce product type.
 *
 * @package StellarPay\Integrations\WooCommerce\ValueObjects
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.8.0
 *
 * @method static self SIMPLE()
 * @method static self VARIABLE()
 * @method static self VARIATION()
 * @method bool isSimple()
 * @method bool isVariable()
 * @method bool isVariation()
 */
class ProductType extends Enum
{
    public const SIMPLE = 'simple';
    public const VARIABLE = 'variable';
    public const VARIATION = 'variation';
}
