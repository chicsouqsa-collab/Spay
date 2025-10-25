<?php

/**
 * This class is used to handle the product related the Stripe rest api requests.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\ProductDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\ProductDTO as StripeResponseProductDTO;

/**
 * Class ProductService
 *
 * @since 1.0.0
 */
class ProductService extends StripeApiService
{
    /**
     * This method creates a product in Stripe.
     *
     * @since 1.0.0
     */
    public function createProduct(ProductDTO $productData): StripeResponseProductDTO
    {
        return StripeResponseProductDTO::fromStripeResponse(
            $this->httpClient->createProduct($productData)
        );
    }

    /**
     * This method retrieves a product from Stripe.
     *
     * @since 1.0.0
     */
    public function updateProduct(string $productId, ProductDTO $productData): StripeResponseProductDTO
    {
        return StripeResponseProductDTO::fromStripeResponse(
            $this->httpClient->updateProduct($productId, $productData)
        );
    }
}
