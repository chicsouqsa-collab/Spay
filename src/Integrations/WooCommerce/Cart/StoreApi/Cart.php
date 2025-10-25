<?php

/**
 * This class is responsible to display subscription order billing data on block cart.
 *
 * @package StellarPay\Integrations\WooCommerce\Cart\StoreApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Cart\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\Views\OrderRecurringTotals;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class Cart
{
    use SubscriptionUtilities;

    /**
     * Extend the Cart Store API.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        woocommerce_store_api_register_endpoint_data(
            [
                'endpoint'        => CartSchema::IDENTIFIER,
                'namespace'       => Constants::PLUGIN_SLUG,
                'data_callback'   => [$this, 'addStellarPayData'],
                'schema_callback' => [$this, 'getDataSchema'],
                'schema_type'     => ARRAY_A,
            ]
        );
    }

    /**
     * Add StellarPay data about the cart.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function addStellarPayData(): array
    {
        return [
           'billingPeriod' => $this->getBillingPeriodForCart(WC()->cart),
           'subscriptionList' => container(OrderRecurringTotals::class)->getSubscriptionListData(),
        ];
    }

     /**
     * Get the StellarPay data schema
     *
     * @since 1.0.0
     */
    public function getDataSchema(): array
    {
        return [
            'billingPeriod' => [
                'description' => esc_html__('The cart billing period based on products in the cart e.g. days and monthly.', 'stellarpay'),
                'type'        => 'string',
                'readonly'    => true,
            ],
            'subscriptionList' => [
                'description' => esc_html__('The subscription data for the cart.', 'stellarpay'),
                'type'        => 'array',
                'readonly'    => true,
            ]
        ];
    }
}
