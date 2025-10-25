<?php

/**
 * This class is responsible to provide the product data for the Stripe rest api request.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use WC_Product;
use WC_Product_Variation;

/**
 * class ProductDataStrategy.
 *
 * @since 1.8.0 Add support for product variation.
 * @since 1.0.0
 */
class ProductDataStrategy implements DataStrategy
{
    /**
     * @since 1.0.0
     */
    private WC_Product $product;

    /**
     * @since 1.8.0
     */
    private ?WC_Product_Variation $productVariation = null;

    /**
     * @since 1.0.0
     */
    public function setProduct(WC_Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @since 1.8.0
     */
    public function setProductVariation(WC_Product_Variation $productVariation): self
    {
        $this->productVariation = $productVariation;

        return $this;
    }

    /**
     * Generate data for the product.
     *
     * @since 1.8.0
     *
     * @return array
     */
    public function generateData(): array
    {
        $data['name'] = $this->product->get_name();
        $data['url'] = esc_url_raw($this->product->get_permalink());

        if ($description = $this->product->get_description()) {
            $data['description'] = $description;
        }

        $data['metadata'] = [
            'product_id' => $this->product->get_id(),
            'product_url' => $this->productVariation instanceof WC_Product_Variation
                ? esc_url_raw($this->productVariation->get_permalink())
                : esc_url_raw($this->product->get_permalink()),
            'site_url' => esc_url(get_site_url()),
        ];

        if ($this->productVariation) {
            $data['metadata']['variation_id'] = $this->productVariation->get_id();
        }

        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the product data.
         *
         * @since 1.0.0
         *
         * @param array $data
         * @param WC_Product $product
         * @param WC_Product_Variation|null $variationProduct
         */
        return apply_filters(
            'stellarpay_wc_stripe_generate_product_data',
            $data,
            $this->product,
            $this->productVariation
        );
    }
}
