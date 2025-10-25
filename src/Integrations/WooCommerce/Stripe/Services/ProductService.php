<?php

/**
 * This class is responsible to provide logic to create or update the Stripe product for the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\ProductRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\ProductDataStrategy;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\ProductDTO;
use StellarPay\PaymentGateways\Stripe\Services\ProductService as BaseProductService;
use WC_Product;
use WC_Product_Variation;

use function StellarPay\Core\container;

/**
 * @since 1.8.0 Add support for product variation
 * @since 1.0.0
 */
class ProductService
{
    /**
     * @since 1.0.0
     */
    protected BaseProductService $productService;

    /**
     * @since 1.0.0
     */
    protected ProductRepository $productRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(BaseProductService $productService, ProductRepository $productRepository)
    {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /**
     * This method creates or updates the product in Stripe.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function createOrUpdate(WC_Product $product): string
    {
        $productVariation = null;

        if ($product instanceof WC_Product_Variation) {
            $productVariation = $product;
            $product = \wc_get_product($productVariation->get_parent_id());
        }

        $stripeProductId = $this->productRepository->getStripeProductId($product);
        $productDTO = $this->getProductDto($product, $productVariation);

        // Create the product in Stripe if it doesn't exist.
        if (! $stripeProductId) {
            $stripeProduct = $this->productService->createProduct($productDTO);
            $stripeProductId = $stripeProduct->getId();

            $this->productRepository->setStripeProductId($product, $stripeProductId);
            $this->productRepository->setStripeLastModifiedProductData($product, $productDTO);

            return $stripeProductId;
        }

        // Update the product in Stripe if it has changed.
        if ($this->productRepository->canUpdateStripeProduct($product, $productDTO)) {
            $this->productService->updateProduct($stripeProductId, $productDTO);
        }

        return $stripeProductId;
    }

    /**
     * This method creates a product DTO.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function getProductDto(WC_Product $product, WC_Product_Variation $productVariation = null): ProductDTO
    {
        $productDataStrategy = container(ProductDataStrategy::class);
        $productStrategy = $productDataStrategy->setProduct($product);

        if ($productVariation instanceof WC_Product_Variation) {
            $productStrategy->setProductVariation($productVariation);
        }

        return ProductDTO::fromProductDataStrategy($productStrategy);
    }
}
