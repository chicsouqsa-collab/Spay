<?php

/**
 * This controller is used to handle StellarPay product variation bulk actions.
 *
 * @package StellarPay\Integrations\WooCommerce\Controllers
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers;

use Automattic\WooCommerce\Utilities\NumberUtil;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariableRepository;
use WC_Product_Variable;
use WC_Product_Variation;

use function StellarPay\Core\container;
use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 */
class VariationBulkActions
{
    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function __invoke(string $bulkAction, array $data, int $productId, array $variationIds): void
    {
        $value = sanitize_text_field($data['value'] ?? '');
        if (! $value || ! $this->isValidValue($value)) {
            return;
        }

        if (!$this->isValidBulkAction($bulkAction)) {
            return;
        }

        $variableProduct = wc_get_product($productId);
        if (
            ! $variableProduct instanceof WC_Product_Variable
            || ! $this->isValidProduct($variableProduct)
        ) {
            return;
        }

        $productType = container(ProductVariableRepository::class)->getProductType($variableProduct);
        if (! $productType instanceof SubscriptionProductType) {
            return;
        }

        foreach ($variationIds as $variationId) {
            $variation = wc_get_product($variationId);

            if (!($variation instanceof WC_Product_Variation)) {
                continue;
            }

            switch ($bulkAction) {
                case 'variable_regular_price':
                    $this->setAmount($variation, $productType, $value);
                    break;

                case 'variable_regular_price_increase':
                    $amount = $this->getAmount($variation, $productType);
                    $increasedAmount = $this->increaseAmount((float) $amount, $value);

                    $this->setAmount($variation, $productType, $increasedAmount);
                    break;

                case 'variable_regular_price_decrease':
                    $amount = $this->getAmount($variation, $productType);
                    $decreasedAmount = $this->decreaseAmount((float) $amount, $value);

                    $this->setAmount($variation, $productType, $decreasedAmount);
                    break;

                case 'variable_sale_price':
                    $this->setSaleAmount($variation, $productType, $value);
                    break;

                case 'variable_sale_price_increase':
                    $saleAmount = $this->getSaleAmount($variation, $productType);
                    $increasedSaleAmount = $this->increaseAmount((float) $saleAmount, $value);

                    $this->setSaleAmount($variation, $productType, $increasedSaleAmount);
                    break;

                case 'variable_sale_price_decrease':
                    $saleAmount = $this->getSaleAmount($variation, $productType);
                    $decreasedSaleAmount = $this->decreaseAmount((float) $saleAmount, $value);

                    $this->setSaleAmount($variation, $productType, $decreasedSaleAmount);
                    break;
            }
        }

        container(ProductVariableRepository::class)->refreshMinVariationData($variableProduct);
    }

    /**
     * @since 1.8.0
     */
    protected function isValidBulkAction(string $bulkAction): bool
    {
        $supportedActions = [
            'variable_regular_price',
            'variable_regular_price_increase',
            'variable_regular_price_decrease',
            'variable_sale_price',
            'variable_sale_price_increase',
            'variable_sale_price_decrease',
        ];

        return in_array($bulkAction, $supportedActions);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    protected function isValidProduct(WC_Product_Variable $variableProduct): bool
    {
        $savedProductType = container(ProductVariableRepository::class)->getProductType($variableProduct);

        return $savedProductType && in_array($savedProductType->getValue(), $this->getProductTypes(), true);
    }

    /**
     * @since 1.8.0
     * @param string $value
     *
     * @return bool
     */
    protected function isValidValue(string $value): bool
    {
        return is_numeric($value)
               || (
                   $this->isPercentualValue($value)
                   && null !== $this->getPercentValue($value)
               );
    }

    /**
     * @since 1.8.0
     */
    protected function getProductTypes(): array
    {
        return [
            SubscriptionProductType::SUBSCRIPTION_PAYMENTS,
            SubscriptionProductType::INSTALLMENT_PAYMENTS,
        ];
    }

    /**
     * @since 1.8.0
     */
    protected function setAmount(WC_Product_Variation $variation, SubscriptionProductType $productType, $amount): void
    {
        $variation->update_meta_data(
            dbMetaKeyGenerator("{$productType}_amount", true),
            $amount
        );

        $variation->save();
    }

    /**
     * @since 1.8.0
     */
    protected function getAmount(WC_Product_Variation $variation, SubscriptionProductType $productType)
    {
        return $variation->get_meta(
            dbMetaKeyGenerator("{$productType}_amount", true),
            true
        );
    }

    /**
     * @since 1.8.0
     */
    protected function setSaleAmount(WC_Product_Variation $variation, SubscriptionProductType $productType, $amount): void
    {
        $variation->update_meta_data(
            dbMetaKeyGenerator("{$productType}_saleAmount", true),
            $amount
        );

        $variation->save();
    }

    /**
     * @since 1.8.0
     */
    protected function getSaleAmount(WC_Product_Variation $variation, SubscriptionProductType $productType)
    {
        return $variation->get_meta(
            dbMetaKeyGenerator("{$productType}_saleAmount", true),
            true
        );
    }

    /**
     * @since 1.8.0
     */
    protected function isPercentualValue(string $value): bool
    {
        return '%' === substr($value, -1);
    }

    /**
     * @since 1.8.0
     */
    protected function getPercentValue(string $value): ?float
    {
        $percentValue = wc_format_decimal(substr($value, 0, -1));

        return is_numeric($percentValue) ? (float) $percentValue : null;
    }

    /**
     * @since 1.8.0
     */
    protected function getUpdatedAmount(float $amount, string $value, string $operation)
    {
        if ($this->isPercentualValue($value)) {
            $percent      = $this->getPercentValue($value) ?? 0.0;
            $changeInAmount = NumberUtil::round(( $amount / 100 ) * $percent, wc_get_price_decimals());
        } else {
            $changeInAmount = is_numeric($value) ? (float) $value : 0.0;
        }

        if ('decrease' === $operation) {
            $changeInAmount *= -1;
        }

        $amount += $changeInAmount;

        return $amount;
    }

    /**
     * @since 1.8.0
     */
    protected function increaseAmount(float $amount, string $value): float
    {
        return $this->getUpdatedAmount($amount, $value, 'increase');
    }

    /**
     * @since 1.8.0
     */
    protected function decreaseAmount(float $amount, string $value): float
    {
        return $this->getUpdatedAmount($amount, $value, 'decrease');
    }
}
