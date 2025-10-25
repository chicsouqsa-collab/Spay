<?php

/**
 * This trait is used to handle the Stripe product related api request.
 *
 * @package StellarPay/Integrations/Stripe/Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\ProductDTO;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\Product;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesProduct
 *
 * @since 1.0.0
 * @property-read StripeClient $client
 */
trait HandlesProduct
{
    /**
     * This method creates a product in Stripe.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createProduct(ProductDTO $productDTO): Product
    {
        try {
            return $this->client->products->create($productDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * This method retrieves a product from Stripe.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updateProduct(string $productId, ProductDTO $productDTO): Product
    {
        try {
            return $this->client->products->update($productId, $productDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
