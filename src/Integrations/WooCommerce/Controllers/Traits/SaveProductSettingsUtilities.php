<?php

/**
 * SaveProductSettingsUtilities
 *
 * This trait is responsible for providing utilities for save product settings operations.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers\Traits;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\Request;
use StellarPay\Core\NumericHelper;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Vendors\StellarWP\Validation\Validator;
use WC_Product;

use function StellarPay\Core\dbMetaKeyGenerator;
use function StellarPay\Core\getNonceActionName;

/**
 * Trait SaveProductSettingsUtilities
 *
 * @since 1.8.0
 */
trait SaveProductSettingsUtilities
{
    /**
     * @since 1.8.0
     */
    private function notUserAuthorized(): bool
    {
        return ! Request::hasPermission('edit_products') // phpcs:ignore WordPress.WP.Capabilities.Unknown
               || ! Request::hasValidNonce(getNonceActionName('woocommerce-product-edit'));
    }

    /**
     * @since 1.8.0
     */
    private function notHaveSettings(): bool
    {
        return ! is_array(Request::post('stellarpay'));
    }

    /**
     * @since 1.8.0
     */
    private function notValidProductType(string $productType): bool
    {
        return ! in_array($productType, ['subscriptionPayments', 'installmentPayments', 'onetimePayments']);
    }

    /**
     * @since 1.8.0
     */
    private function getValidationRules(array $optionsAllowList): array
    {
        $rules = [
            'billingPeriod' => ['in:day,week,month,year,custom'],
            'numberOfPayments' => ['integer', 'min:2'],
            'amount' => ['numeric'],
            'saleAmount' => ['optional', 'numeric',],
            'recurringFrequency' => ['integer', 'min:2'],
            'recurringPeriod' => ['in:day,week,month,year'],
            'saleFromDate' => ['optional', 'dateTime:Y-m-d'],
            'saleToDate' => ['optional', 'dateTime:Y-m-d'],
        ];

        return  array_intersect_key($rules, $optionsAllowList);
    }

    /**
     * Sync the amount value with WooCommerce price fields e.g.
     * `price` and `regular_price`.
     *
     * Also remove the sale price fields.
     *
     * @since 1.8.0
     * @throws BindingResolutionException
     * @throws \Exception
     */
    private function syncAmountValueWithWooCommercePriceFields(WC_Product $product): void
    {
        $subscriptionProduct = ProductFactory::makeFromProduct($product);

        if (! $subscriptionProduct) {
            return;
        }

        $product->set_regular_price($subscriptionProduct->getRegularAmount('edit'));
        $product->set_sale_price($subscriptionProduct->getSaleAmount('edit'));

        $saleFromDate = $subscriptionProduct->getSaleFromDate('edit');
        $saleToDate = $subscriptionProduct->getSaleToDate('edit');

        $product->set_date_on_sale_from(
            $saleFromDate
                ? DateTime::createFromImmutable($saleFromDate)
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->getTimestamp()
                : ''
        );
        $product->set_date_on_sale_to(
            $saleToDate
                ? DateTime::createFromImmutable($saleToDate)
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->getTimestamp()
                : ''
        );

        $product->save();
    }

    /**
     * @since 1.8.0
     */
    private function getSettings(): array
    {
        return Request::post('stellarpay');
    }

    /**
     * @since 1.8.0
     * @throws \Exception
     */
    private function save(WC_Product $product, SubscriptionProductType $productType, array $options): void
    {
        $validationRules = $this->filterValidationRules(
            $this->getValidationRules($options),
            $options
        );

        $validator = new Validator($validationRules, $options);

        $safeValues = $validator->validated();
        $ignoreOptionsWithError = array_keys($validator->errors());

        foreach ($safeValues as $optionName => $optionValue) {
            // Skip values which are invalid.
            if (in_array($optionName, $ignoreOptionsWithError, true)) {
                continue;
            }

            if ($optionValue instanceof DateTimeImmutable) {
                $dateObject = new DateTime($optionValue->format('Y-m-d'), wp_timezone());

                switch ($optionName) {
                    case 'saleFromDate':
                        $dateObject->setTime(0, 0);
                        break;
                    case 'saleToDate':
                        $dateObject->setTime(23, 59, 59);
                        break;
                }

                $dateObject->setTimezone(new DateTimeZone('UTC'));

                $product->update_meta_data(
                    dbMetaKeyGenerator("{$productType}_{$optionName}", true),
                    (string) $dateObject->getTimestamp()
                );

                continue;
            }

            $product->update_meta_data(
                dbMetaKeyGenerator("{$productType}_{$optionName}", true),
                $optionValue
            );
        }

        $product->save();
    }

    /**
     * This function customized existing validation rule to achieve results.
     *
     * @since 1.8.0
     */
    private function filterValidationRules(array $rules, array $options): array
    {
        // Sale amount should be minimum than regular amount.
        // But the regular amount should be greater that one to send max rule. This is the "max" rule limitation.
        if (array_key_exists('saleAmount', $options) && array_key_exists('amount', $options)) {
            $regularAmount = (float) $options['amount'];

            if ($regularAmount > 1) {
                $rules['saleAmount'] = array_merge(
                    $rules['saleAmount'],
                    [ 'max:' . ( NumericHelper::getOnePointLessValue((float) $options['amount']) ?: 0 )]
                );
            }
        }

        return $rules;
    }
}
