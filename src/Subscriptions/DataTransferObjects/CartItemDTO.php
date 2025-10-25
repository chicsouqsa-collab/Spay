<?php

/**
 * This class is used to store data of cart item.
 *
 * @package StellarPay\Subscriptions\CartItem
 * @since 1.5.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\DataTransferObjects;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;

/**
 * Class CartItemDTO
 *
 * @since 1.5.0
 */
class CartItemDTO
{
    /**
     * @since 1.8.0 Change the var type from "Product" to "SubscriptionProduct"
     * @since 1.5.0
     */
    public SubscriptionProduct $product;

    /**
     * @since 1.5.0
     */
    public int $quantity;

    /**
     * @since 1.5.0
     *
     * @param array $cartItem A single array item from WC()->cart->get_cart_contents()
     *
     * @throws BindingResolutionException
     */
    public static function fromWooCartItem(array $cartItem): self
    {
        $self = new self();

        $self->product = ProductFactory::makeFromProduct($cartItem['data']);
        $self->quantity = $cartItem['quantity'];

        return $self;
    }
}
