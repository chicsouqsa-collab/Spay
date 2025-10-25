<?php

/**
 * This class is used to manage the product data for Woocommerce and Stripe integration.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PriceDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\ProductDTO;
use WC_Product;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Class ProductRepository
 *
 * @since 1.0.0
 */
class ProductRepository
{
    /**
     * @since 1.0.0
     */
    private string $stripeProductIdKey;

    /**
     * @since 1.0.0
     */
    private string $stripeLastModifiedProductData;

    /**
     * @since 1.0.0
     */
    private string $stripePriceIdKeyPrefix;

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->stripeProductIdKey = dbMetaKeyGenerator('stripe_product_id', true);
        $this->stripeLastModifiedProductData = dbMetaKeyGenerator('stripe_last_modified_product_data', true);
        $this->stripePriceIdKeyPrefix = dbMetaKeyGenerator('stripe_price_id', true);
    }

    /**
     * This method get meta key in which we store stripe product id.
     *
     * @since 1.0.0
     */
    public function getStripeProductIdKey(): string
    {
        return $this->stripeProductIdKey;
    }

    /**
     * This method get meta key in which we store stripe last modified product data.
     *
     * @since 1.0.0
     */
    public function getStripeLastModifiedProductDataKey(): string
    {
        return $this->stripeLastModifiedProductData;
    }

    /**
     * This method get meta key prefix in which we store stripe price id.
     *
     * @since 1.0.0
     */
    public function getStripePriceIdKeyPrefix(): string
    {
        return $this->stripePriceIdKeyPrefix;
    }

    /**
     * This method get the stripe product id for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function getStripeProductId(WC_Product $product): ?string
    {
        $stripeProductId = (string)$product->get_meta($this->getStripeProductIdKey(), true, 'edit');

        return empty($stripeProductId) ? null : $stripeProductId;
    }

    /**
     * This method set the stripe product id for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function setStripeProductId(WC_Product $product, string $stripeProductId): void
    {
        $product->update_meta_data($this->getStripeProductIdKey(), $stripeProductId);
        $product->save();
    }

    /**
     * This method get the stripe last modified product data for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function getStripeLastModifiedProductData(WC_Product $product): ?string
    {
        return $product->get_meta($this->getStripeLastModifiedProductDataKey(), true, 'edit');
    }

    /**
     * This method get the stripe last modified product data for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function setStripeLastModifiedProductData(WC_Product $product, ProductDTO $productDTO): void
    {
        $product->update_meta_data(
            $this->getStripeLastModifiedProductDataKey(),
            md5(maybe_serialize($productDTO->toArray()))
        );
        $product->save();
    }

    /**
     * This method check if the product is updated.
     *
     * @since 1.0.0
     */
    public function canUpdateStripeProduct(WC_Product $product, ProductDTO $productDTO): bool
    {
        $lastModifiedProductData = $this->getStripeLastModifiedProductData($product);
        $newProductData = md5(maybe_serialize($productDTO->toArray()));

        return $lastModifiedProductData !== $newProductData;
    }

    /**
     * This method gets the stripe price id for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function getStripePriceId(WC_Product $product, PriceDTO $priceDTO): string
    {
        $metaKey = $this->getStripePriceIdKeyPrefix() . '_' . md5(maybe_serialize($priceDTO->toArray()));
        return $product->get_meta($metaKey, true);
    }

    /**
     * This method gets the stripe price id for a given WooCommerce product.
     *
     * @since 1.0.0
     */
    public function setStripePriceId(WC_Product $product, PriceDTO $priceDTO, string $stripePriceId): bool
    {
        $metaKeyNameSuffix = md5(maybe_serialize($priceDTO->toArray()));
        $metaKey = $this->getStripePriceIdKeyPrefix() . '_' . $metaKeyNameSuffix ;
        $product->update_meta_data($metaKey, $stripePriceId);

        return (bool) absint($product->save());
    }
}
