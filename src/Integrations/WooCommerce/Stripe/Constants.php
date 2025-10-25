<?php

/**
 * Woocommerce Stripe Payment Gateway Constants.
 *
 * This class is responsible for managing constants for the Stripe payment gateway.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe;

/**
 * Class Constants.
 *
 * @since 1.0.0
 */
class Constants
{
    /**
     * The gateway id.
     *
     * @var string
     * @since 1.0.0
     */
    public const GATEWAY_ID = \StellarPay\Core\Constants::PLUGIN_SLUG . '-stripe';
}
