<?php

/**
 * This class is responsible to display subscription product billing data on block cart.
 *
 * @package StellarPay\Integrations\WooCommerce\Cart\StoreApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Cart\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Views\OrderRecurringTotals;
use WC_Product;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class CartItem
{
    /**
     * Extend the CartItem Store API.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        woocommerce_store_api_register_endpoint_data(
            [
                'endpoint'        => CartItemSchema::IDENTIFIER,
                'namespace'       => Constants::PLUGIN_SLUG,
                'data_callback'   => [$this, 'addStellarPayData'],
                'schema_callback' => [$this, 'getDataSchema'],
                'schema_type'     => ARRAY_A,
            ]
        );
    }

    /**
     * Add StellarPay data about the cart item.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function addStellarPayData(array $cartItem): array
    {
        $product = $cartItem['data'] ?? false;

        if (! $product instanceof WC_Product) {
            return $cartItem;
        }

        $product = ProductFactory::makeFromProduct($product);

        if (!$product) {
            return $cartItem;
        }

        return [
            'formattedFrequency' => container(OrderRecurringTotals::class)
                ->getPriceWithFormattedFrequencyForCartItem(
                    $product,
                    (float)$product->getAmount('edit'),
                    'block'
                ),
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
                'description' => esc_html__('The product billing period e.g. days and monthly.', 'stellarpay'),
                'type'        => 'string',
                'readonly'    => true,
            ],
        ];
    }
}
