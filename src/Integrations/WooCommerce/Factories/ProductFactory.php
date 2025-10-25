<?php

/**
 * This class is uses to create a subscription product type class object based on Woocommerce settings.
 *
 * @package StellarPay\Integrations\WooCommerce\Factories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Factories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Simple\InstallmentPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\Product as ProductModel;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Simple\SubscriptionPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable\SubscriptionPaymentsProduct as VariableSubscriptionPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variable\InstallmentPaymentsProduct as VariableInstallmentPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation\SubscriptionPaymentsProduct as VariationSubscriptionPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Variation\InstallmentPaymentsProduct as VariationInstallmentPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariationRepository;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

use function StellarPay\Core\container;

/**
 * @since 1.8.0 Add support for product variation.
 * @since 1.0.0
 */
class ProductFactory
{
    /**
     * @since 1.0.0
     *
     * @return InstallmentPaymentsProduct|SubscriptionPaymentsProduct|VariableInstallmentPaymentsProduct|VariableSubscriptionPaymentsProduct|VariationInstallmentPaymentsProduct|VariationSubscriptionPaymentsProduct
     * @throws BindingResolutionException
     */
    public static function makeFromProduct(WC_Product $product): ?ProductModel
    {
        if ($product instanceof WC_Product_Variation) {
            return self::makeFromProductVariation($product);
        }

        if ($product instanceof WC_Product_Variable) {
            return self::makeFromProductVariable($product);
        }

        $productType = container(ProductRepository::class)->getProductType($product);

        if (null === $productType || $productType->isOnetimePayments()) {
            return null;
        }

        switch (true) {
            case $productType->isSubscriptionPayments():
                return new SubscriptionPaymentsProduct($product);
            case $productType->isInstallmentPayments():
                return new InstallmentPaymentsProduct($product);
        }

        throw new InvalidArgumentException(esc_html("invalid subscription product type: $productType"));
    }

    /**
     * @since 1.8.0
     *
     * @return VariationInstallmentPaymentsProduct|VariationSubscriptionPaymentsProduct
     * @throws BindingResolutionException
     */
    public static function makeFromProductVariation(WC_Product_Variation $productVariation): ?ProductModel
    {
        $productType = container(ProductVariationRepository::class)->getProductType($productVariation);

        if (null === $productType || $productType->isOnetimePayments()) {
            return null;
        }

        switch (true) {
            case $productType->isSubscriptionPayments():
                return new VariationSubscriptionPaymentsProduct($productVariation);
            case $productType->isInstallmentPayments():
                return new VariationInstallmentPaymentsProduct($productVariation);
        }

        throw new InvalidArgumentException(esc_html("invalid subscription product type: $productType"));
    }

    /**
     * @since 1.8.0
     *
     * @return VariableInstallmentPaymentsProduct|VariableSubscriptionPaymentsProduct
     * @throws BindingResolutionException
     */
    public static function makeFromProductVariable(WC_Product_Variable $productVariable): ?ProductModel
    {
        $productType = container(ProductVariableRepository::class)->getProductType($productVariable);

        if (null === $productType || $productType->isOnetimePayments()) {
            return null;
        }

        switch (true) {
            case $productType->isSubscriptionPayments():
                return new VariableSubscriptionPaymentsProduct($productVariable);
            case $productType->isInstallmentPayments():
                return new VariableInstallmentPaymentsProduct($productVariable);
        }

        throw new InvalidArgumentException(esc_html("invalid subscription product type: $productType"));
    }
}
