<?php

/**
 * This class is used to update product data meta-box on the product edit page
 *
 * @package StellarPay\Integrations\WooCommerce\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\ProductEditPage;

use DateTime;
use DateTimeZone;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Simple\InstallmentPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Simple\SubscriptionPaymentsProduct;
use StellarPay\Integrations\WooCommerce\Repositories\ProductRepository;
use WC_Product;

use function StellarPay\Core\getNonceActionName;

/**
 * @since 1.0.0
 */
class ProductEditPage
{
    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        $scriptId = 'stellarpay-woocommerce-product-edit-page';
        $script = new EnqueueScript($scriptId, "/build/$scriptId.js");

        $data = [
            'nonce' => wp_create_nonce(getNonceActionName('woocommerce-product-edit')),
            'settings' => $this->getProductSettings(),
            'woocommerce' => [
                'currencySymbol' => get_woocommerce_currency_symbol()
            ],
            'i18n' => [
                'recurringFrequencyError' => esc_html__('Please enter 2 or more periods.', 'stellarpay'),
                'numberOfPaymentsError' => esc_html__('Please enter 2 or more installments.', 'stellarpay'),
                'regularAmountLessThanSalesAmountError' => esc_html__('Please enter in a value less than the regular price.', 'stellarpay'),
            ]
        ];

        $script->loadStyle()
            ->registerLocalizeData('stellarPayProductEditPage', $data)
            ->registerTranslations()
            ->loadInFooter()
            ->enqueue();
    }

    /**
     * @since 1.0.0
     */
    private function getProductSettings(): array
    {
        global $post;
        $product = wc_get_product($post->ID);
        $productType = $this->getProductType($product);

        if (! $productType) {
            return ['productType' => 'onetimePayments'];
        }

        $products = [
            new SubscriptionPaymentsProduct($product),
            new InstallmentPaymentsProduct($product)
        ];

        $results = ['productType' => $productType];
        foreach ($products as $product) {
            $productType = $product->getProductType()->getValue();
            $results[$productType] = $this->getProductTypeSettings($product);
        }

        return $results;
    }

    /**
     * @since 1.8.0
     */
    private function getProductType(WC_Product $product): string
    {
        return $product->get_meta(ProductRepository::getProductTypeKey());
    }

    /**
     * @since 1.1.0
     */
    private function getProductTypeSettings(SubscriptionProduct $product): array
    {
        $amount = $product->getRegularAmount('edit');
        $saleAmount = $product->getSaleAmount('edit');

        $data = [
            'amount' => wc_format_localized_price($amount),
            'saleAmount' => wc_format_localized_price($saleAmount),
            'billingPeriod' => $product->getBillingPeriod('edit'),
            'recurringPeriod' => $product->getRecurringPeriod('edit'),
            'recurringFrequency' => $product->getRecurringFrequency('edit')
        ];

        $saleFromDate = $product->getSaleFromDate('edit');
        $data['saleFromDate'] = '';
        if ($saleFromDate) {
            $gmtSaleFromDate = DateTime::createFromImmutable($saleFromDate);
            $gmtSaleFromDate->setTimezone(new DateTimeZone('UTC'));

            $data['saleFromDate'] = $gmtSaleFromDate->format('Y-m-d H:i:s') . 'Z';
        }

        $saleToDate = $product->getSaleToDate('edit');
        $data['saleToDate'] = '';
        if ($saleToDate) {
            $gmtSaleFromDate = DateTime::createFromImmutable($saleToDate);
            $gmtSaleFromDate->setTimezone(new DateTimeZone('UTC'));

            $data['saleToDate'] = $gmtSaleFromDate->format('Y-m-d H:i:s') . 'Z';
        }

        if (method_exists($product, 'getNumberOfPayments')) {
            $data['numberOfPayments'] = $product->getNumberOfPayments('edit');
        }

        return $data;
    }
}
