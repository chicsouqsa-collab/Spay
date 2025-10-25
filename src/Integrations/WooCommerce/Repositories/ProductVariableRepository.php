<?php

/**
 * This class is responsible to provide repository for variable product.
 *
 * @package StellarPay\Integrations\WooCommerce\Repositories
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Repositories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use WC_Product_Variable;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 */
class ProductVariableRepository
{
    /**
     * @since 1.8.0
     */
    protected ProductRepository $productRepository;

    /**
     * @since 1.8.0
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @since 1.8.0
     */
    public function getProductType(WC_Product_Variable $variable): ?SubscriptionProductType
    {
        return $this->productRepository->getProductType($variable);
    }

    /**
     * @since 1.8.0
     */
    public static function getMinVariationDataKey(): string
    {
        return dbMetaKeyGenerator('minVariationData', true);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getMinVariationMetaData(WC_Product_Variable $variable, $context = 'view')
    {
        $minVariationMetadataKey = self::getMinVariationDataKey();
        $minVariationData = $variable->get_meta($minVariationMetadataKey, true, $context);

        if (!empty($minVariationData)) {
            return $minVariationData;
        }

        return $this->refreshMinVariationData($variable);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function refreshMinVariationData(WC_Product_Variable $variable): ?array
    {
        $minVariationData = $this->getMinVariationData($variable, $this->getProductType($variable));

        $variable->update_meta_data(self::getMinVariationDataKey(), $minVariationData);
        $variable->save();

        return $minVariationData;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getVariableAmount(WC_Product_Variable $variable, $context = 'view'): string
    {
        $minVariationData = $this->getMinVariationMetaData($variable, $context);

        return $minVariationData['amount'] ?? '';
    }

    /**
     * @since 1.8.0
     */
    public function getVariableBillingPeriod(WC_Product_Variable $variable, $context = 'view'): string
    {
        $minVariationData = $this->getMinVariationMetaData($variable, $context);

        return $minVariationData['billingPeriod'] ?? '';
    }

    /**
     * @since 1.8.0
     */
    public function getVariableRecurringFrequency(WC_Product_Variable $variable, $context = 'view'): int
    {
        $minVariationData = $this->getMinVariationMetaData($variable, $context);

        return (int) $minVariationData['recurringFrequency'];
    }

    /**
     * @since 1.8.0
     */
    public function getVariableRecurringPeriod(WC_Product_Variable $variable, $context = 'view'): string
    {
        $minVariationData = $this->getMinVariationMetaData($variable, $context);

        return $minVariationData['recurringPeriod'] ?? '';
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getVariableNumberOfPayments(WC_Product_Variable $variable, $context = 'view'): int
    {
        $minVariationData = $this->getMinVariationMetaData($variable, $context);

        return $minVariationData['numberOfPayments'] ? absint($minVariationData['numberOfPayments']) : 0;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    protected function getVariations(WC_Product_Variable $variableProduct): array
    {
        $productVariations = $variableProduct->get_children();

        if (! $productVariations) {
            return [];
        }

        return array_filter(
            array_map(
                function ($variationId) {
                    $productVariation = wc_get_product($variationId);
                    $product = ProductFactory::makeFromProduct($productVariation);

                    return $this->getVariationData($product);
                },
                $productVariations
            )
        );
    }

    /**
     * @since 1.8.0
     */
    protected function getVariationData(SubscriptionProduct $product): array
    {
        $amount = $product->getAmount();
        $billingPeriod = $product->getBillingPeriod();

        $recurringFrequency = null;
        $recurringPeriod = null;
        if ('custom' === $billingPeriod) {
            $recurringFrequency = $product->getRecurringFrequency();
            $recurringPeriod = $product->getRecurringPeriod();
        }

        if ($product->getProductType()->isInstallmentPayments()) {
            $numberOfPayments = $product->getNumberOfPayments(); // @phpstan-ignore-line
        }

        return [
            'amount' => $amount,
            'billingPeriod' => $billingPeriod,
            'recurringFrequency' => $recurringFrequency ?? 0,
            'recurringPeriod' => $recurringPeriod ?? '',
            'numberOfPayments' => $numberOfPayments ?? 0,
        ];
    }


    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getMinVariationData(WC_Product_Variable $variableProduct, SubscriptionProductType $productType): ?array
    {
        if ($productType->isOnetimePayments()) {
            return null;
        }

        $variations = $this->getVariations($variableProduct);

        if (empty($variations)) {
            return null;
        }

        if (count($variations) === 1) {
            return current($variations);
        }

        $isInstallmentPayments = $productType->isInstallmentPayments();

        usort($variations, static function ($a, $b) use ($isInstallmentPayments) {
            // Billing period priority lookup
            $billingPeriods = ['day' => 1, 'week' => 2, 'month' => 3, 'year' => 4];

            // Get effective billing periods accounting for custom types
            $aPeriod = 'custom' === $a['billingPeriod'] ? $a['recurringPeriod'] : $a['billingPeriod'];
            $bPeriod = 'custom' === $b['billingPeriod'] ? $b['recurringPeriod'] : $b['billingPeriod'];

            // 1. Compare billing periods
            $periodComparison = $billingPeriods[$aPeriod] <=> $billingPeriods[$bPeriod];
            if (0 !== $periodComparison) {
                return $periodComparison;
            }

            // 2. Compare frequencies if periods are equal
            $frequencyComparison = $a['recurringFrequency'] <=> $b['recurringFrequency'];
            if (0 !== $frequencyComparison) {
                return $frequencyComparison;
            }

            // 3. Compare amounts
            $amountComparison = $a['amount'] <=> $b['amount'];
            if (0 !== $amountComparison) {
                return $amountComparison;
            }

            // 4. Compare number of payments
            if ($isInstallmentPayments) {
                return $a['numberOfPayments'] <=> $b['numberOfPayments'];
            }

            return $amountComparison;
        });

        $minVariationData = current($variations);
        $maxVariationData = end($variations);

        $minVariationData['hasVariationsSameData'] = $minVariationData === $maxVariationData;

        return $minVariationData;
    }

    /**
     * @since 1.8.0
     */
    public function hasVariationsSameData(WC_Product_Variable $variableProduct): bool
    {
        $minVariationData = $this->getMinVariationMetaData($variableProduct);

        return $minVariationData['hasVariationsSameData'] ?? false;
    }
}
