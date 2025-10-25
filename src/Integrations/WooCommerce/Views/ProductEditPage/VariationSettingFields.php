<?php

/**
 * This class uses to modify product variations setting fields on the WooCommerce product edit page.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\ProductEditPage
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\ProductEditPage;

use DateTimeZone;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;
use StellarPay\Core\ValueObjects\SubscriptionProductType;
use StellarPay\Integrations\WooCommerce\Repositories\ProductVariationRepository;
use WC_Product_Variation;
use WP_Post;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 */
class VariationSettingFields
{
    /**
     * @since 1.8.0
     */
    protected ProductVariationRepository $productVariationRepository;

    /**
     * @since 1.8.0
     */
    public function __construct(ProductVariationRepository $productVariationRepository)
    {
        $this->productVariationRepository = $productVariationRepository;
    }

    /**
     * @since 1.8.0
     */
    public function __invoke(int $loop, array $variationData, WP_Post $variation): void
    {
        $variationProduct = wc_get_product($variation->ID);
        if (! $variationProduct instanceof WC_Product_Variation) {
            return;
        }

        $saveProductType = $this->productVariationRepository->getProductType($variationProduct);

        if (null === $saveProductType) {
            $saveProductType = SubscriptionProductType::ONETIME_PAYMENTS();
        }

        $productTypes = [
            SubscriptionProductType::SUBSCRIPTION_PAYMENTS(),
            SubscriptionProductType::INSTALLMENT_PAYMENTS(),
        ];

        foreach ($productTypes as $productType) {
            $saleFromDate = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_saleFromDate', true), true);
            $saleToDate = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_saleToDate', true), true);
            $hasSaleDates = $saleFromDate || $saleToDate;

            echo sprintf(
                '<div class="%1$s" style="%2$s">',
                esc_attr("stellarpay_$productType"),
                $saveProductType->equals($productType) ? '' : 'display: none;'
            );
            $this->addPriceFields($productType, $variation, $loop, $hasSaleDates);
            $this->addSaleDateFields($productType, $loop, $saleFromDate, $saleToDate);
            $this->addBillingPeriodSettingFields($productType, $variation, $loop);
            echo '</div>';
        }
    }

    /**
     * @since 1.8.0
     */
    private function addAmountSettingField(SubscriptionProductType $productType, WP_Post $variation, int $loop): void
    {
        $name = sprintf(
            'stellarpay[%1$s][%2$d]',
            $productType->getValue(),
            $loop
        );

        $amount = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_amount', true), true);

        woocommerce_wp_text_input(
            [
                'id'            => "{$name}[amountFormatted]",
                'value'         => wc_format_localized_price($amount),
                'label'         => sprintf(
                    '%1$s (%2$s)',
                    esc_html__('Amount', 'stellarpay'),
                    get_woocommerce_currency_symbol()
                ),
                'data_type'     => 'price',
                'description'   => esc_html__('This is the amount that customers pay at regular intervals.', 'stellarpay'),
                'desc_tip'      => true,
                'class'         => 'js-stellarpay-regular-amount-form-field',
                'wrapper_class' => 'form-row form-row-first',
                'placeholder'   => esc_html__('Amount (required)', 'stellarpay'),
                'custom_attributes' => [
                    'data-input' => "{$name}[amount]"
                ]
            ]
        );
        woocommerce_wp_hidden_input(
            [
                'id'            => "{$name}[amount]",
                'name'          => $name,
                'value'         => $amount,
            ]
        );
    }

    /**
     * @since 1.8.0
     */
    private function addSaleAmountSettingField(SubscriptionProductType $productType, WP_Post $variation, int $loop, bool $hasSaleDates): void
    {
        $name = sprintf(
            'stellarpay[%1$s][%2$d]',
            $productType->getValue(),
            $loop
        );

        $saleAmount = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_saleAmount', true), true);
        ?>
        <div class="form-row form-row-last sp-relative">
            <?php
            woocommerce_wp_text_input(
                [
                    'id'            => "{$name}[saleAmountFormatted]",
                    'value'         => wc_format_localized_price($saleAmount),
                    'class'         => 'js-stellarpay-sales-amount-form-field',
                    'label'         => sprintf(
                        '%1$s (%2$s) <button class="js-sale-date-fields-switcher sp-bg-transparent sp-text-[#2271B1] underline sp-underline sp-border-0 sp-pl-0 sp-cursor-pointer">%3$s</button>',
                        esc_html__('Sale amount', 'stellarpay'),
                        get_woocommerce_currency_symbol(),
                        $hasSaleDates
                            ? esc_html__('Cancel schedule', 'stellarpay')
                            : esc_html__('Schedule', 'stellarpay')
                    ),
                    'data_type'     => 'price',
                    'description'   => esc_html__('Set a discount amount that customers pay at regular intervals.', 'stellarpay'),
                    'desc_tip'      => true,
                    'wrapper_class' => 'sp-mb-0',
                    'placeholder'   => esc_html__('Sale amount', 'stellarpay'),
                    'custom_attributes' => [
                        'data-input' => "{$name}[saleAmount]"
                    ]
                ]
            );

            woocommerce_wp_hidden_input([
                'id'            => "{$name}[saleAmount]",
                'name'          => $name,
                'value'         => $saleAmount,
            ]);
            ?>
        </div>
        <?php
    }

    /**
     * @since 1.8.0
     */
    private function addSaleFromDateFields(SubscriptionProductType $productType, string $saleFromDate, int $loop): void
    {
        $name = esc_attr(
            sprintf(
                'stellarpay[%1$s][%2$d][saleFromDate]',
                $productType->getValue(),
                $loop
            )
        );

        if ($saleFromDate) {
            $saleFromDate = wp_date('Y-m-d H:i:s', absint($saleFromDate), new DateTimeZone('UTC'))  . 'Z';
        }

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
        <div class="form field <?php echo $name; ?>_field form-row form-row-first">
            <label for="<?php echo $name ?>"><?php echo esc_html__('Sale start date', 'stellarpay'); ?></label>
            <div
                data-selecteddate="<?php echo esc_attr($saleFromDate); ?>"
                data-fieldname="<?php echo $name ?>"
                data-placeholder="<?php esc_attr_e('From... YYYY-MM-DD', 'stellarpay'); ?>"
                class="js-sale-date-field-container"
            ></div>
        </div>
        <?php
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @since 1.8.0
     */
    private function addSaleToDateFields(SubscriptionProductType $productType, string $saleToDate, int $loop): void
    {
        $name = esc_attr(
            sprintf(
                'stellarpay[%1$s][%2$d][saleToDate]',
                $productType->getValue(),
                $loop
            )
        );

        if ($saleToDate) {
            $saleToDate = wp_date('Y-m-d H:i:s', absint($saleToDate), new DateTimeZone('UTC'))  . 'Z';
        }

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
        <div class="form field <?php echo $name; ?>_field form-row form-row-last">
            <label for="<?php echo $name ?>"><?php echo esc_html__('Sale end date', 'stellarpay'); ?></label>
            <div
                data-selectedDate="<?php echo esc_attr($saleToDate); ?>"
                data-fieldName="<?php echo $name ?>"
                data-placeholder="<?php esc_attr_e('To... YYYY-MM-DD', 'stellarpay'); ?>"
                class="js-sale-date-field-container"
            ></div>
        </div>
        <?php
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @since 1.8.0
     */
    private function addPriceFields(SubscriptionProductType $productType, WP_Post $variation, int $loop, bool $hasSaleDates): void
    {
        echo '<div class="stellarpay-price-fields">';
        $this->addAmountSettingField($productType, $variation, $loop);
        $this->addSaleAmountSettingField($productType, $variation, $loop, $hasSaleDates);
        echo '</div>';
    }

    /**
     * @since 1.8.0
     */
    private function addSaleDateFields(SubscriptionProductType $productType, int $loop, string $saleFromDate, string $saleToDate): void
    {
        echo sprintf(
            '<div class="stellarpay-sale-date-fields" style="%1$s">',
            $saleFromDate || $saleToDate ? '' : 'display:none;'
        );
        $this->addSaleFromDateFields($productType, $saleFromDate, $loop);
        $this->addSaleToDateFields($productType, $saleToDate, $loop);
        echo '</div>';
    }

    /**
     * @since 1.8.0
     */
    private function addBillingPeriodSettingFields(SubscriptionProductType $productType, WP_Post $variation, int $loop): void
    {
        $wrapperClass = 'stellarpay-billing-period-fields sp-clear-both';

        if ($productType->isSubscriptionPayments()) {
            $wrapperClass .= ' sp-flex';
        }

        ?>
        <div class="<?php echo esc_attr($wrapperClass); ?>">
            <div class="form-row form-row-first form-field sp-flex sp-items-start sp-gap-x-4">
                <?php
                $this->addBillingPeriodField($productType, $variation, $loop);
                $this->addAdditionalSettingFieldForCustomBillingPeriod($productType, $variation, $loop);
                ?>
            </div>
            <div class="form-row form-row-last form-field sp-flex">
                <?php
                if ($productType->isInstallmentPayments()) {
                    $this->addNumberOfPaymentsSettingField($variation, $loop);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * @since 1.8.0
     */
    private function addBillingPeriodField(SubscriptionProductType $productType, WP_Post $variation, int $loop): void
    {
        $billingName = sprintf(
            'stellarpay[%1$s][%2$d][billingPeriod]',
            $productType->getValue(),
            $loop
        );

        $billingPeriod = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_billingPeriod', true), true);

        woocommerce_wp_select(
            [
                'id'            => $billingName,
                'name'          => $billingName,
                'value'         => $billingPeriod,
                'label'         => esc_html__('Billing Period', 'stellarpay'),
                'options'       => array_merge(
                    SubscriptionPeriod::selectFieldOptions(),
                    ['custom' => esc_html__('Custom', 'stellarpay')]
                ),
                'description'   => esc_html__('This is how often the amount will be charged to customers.', 'stellarpay'),
                'desc_tip'      => true,
                'wrapper_class' => 'sp-flex-1 sp-max-w-full sp-mt-0'
            ]
        );
    }

    /**
     * @since 1.8.0
     */
    private function addNumberOfPaymentsSettingField(WP_Post $variation, int $loop): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        $productType = SubscriptionProductType::INSTALLMENT_PAYMENTS;
        $fieldId = sprintf('stellarpay[%1$s][%2$d][numberOfPayments]', $productType, $loop);
        $numberOfPayments = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_numberOfPayments', true), true) ?: 2;
        $descriptionTipLabel = esc_html__('This is the number of times the customer will be charged the amount above.', 'stellarpay');
        ?>
        <div class="sp-flex-1 sp-relative">
            <label for="<?php echo $fieldId; ?>">
                <?php esc_html_e('Number of Installments', 'stellarpay'); ?>
            </label>
            <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php echo $descriptionTipLabel; ?>"></span>
            <div class="sp-w-full sp-flex sp-items-center sp-border sp-border-solid sp-border-[#8c8f94] sp-rounded">
                <div class="sp-p-0 sp-border-0 sp-border-r sp-border-solid sp-border-[#8c8f94] sp-flex-1">
                    <input
                        name="<?php echo $fieldId; ?>" id="<?php echo $fieldId; ?>"
                        type="number"
                        class="js-stellarpay-number-of-payments-form-field sp-border-0"
                        placeholder="2"
                        min="2"
                        max="9999"
                        autocomplete="off"
                        value="<?php echo absint($numberOfPayments); ?>"
                    >
                </div>
                <div class="sp-px-3">
                    <?php esc_html_e('Payments', 'stellarpay');?>
                </div>
            </div>
        </div>
        <?php
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @since 1.8.0
     */
    private function addAdditionalSettingFieldForCustomBillingPeriod(SubscriptionProductType $productType, WP_Post $variation, int $loop): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        $inputFieldName = sprintf(
            'stellarpay[%1$s][%2$d][recurringFrequency]',
            $productType->getValue(),
            $loop
        );

        $selectFieldName = sprintf(
            'stellarpay[%1$s][%2$d][recurringPeriod]',
            $productType->getValue(),
            $loop
        );

        $recurringFrequency = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_recurringFrequency', true), true) ?: 2;
        $recurringPeriod = get_post_meta($variation->ID, dbMetaKeyGenerator($productType . '_recurringPeriod', true), true);

        $hasCustomBillingPeriod = $this->productVariationRepository->hasCustomBillingPeriod($variation->ID, $productType);
        ?>
        <div
            class="form-field form-row sp-flex sp-flex-1 sp-flex-col sp-border sp-border-solid sp-border-[#8c8f94] sp-rounded sp-max-h-[40px] sp-self-end sp-mb-4 sp-relative sp-top-[4px] js-stellarpay-recurring-billing-period-form-fields"
            style="<?php echo $hasCustomBillingPeriod ? '' : 'display:none;'; ?>"
        >
            <div class="sp-flex sp-flex-1 sp-items-center">
                <div class="sp-px-3 sp-text-sm">
                    <label for="<?php echo $inputFieldName; ?>" class="screen-reader-text">
                        <?php esc_html_e('Recurring Frequency', 'stellarpay'); ?>
                    </label>
                    <?php esc_html_e('Every', 'stellarpay'); ?>
                </div>
                <div class="sp-px-0 sp-border-0 sp-border-l sp-border-solid sp-border-[#8c8f94]">
                    <input
                        id="<?php echo $inputFieldName; ?>"
                        name="<?php echo $inputFieldName; ?>"
                        type="number"
                        class="js-stellarpay-recurring-frequency-form-field sp-w-16 sp-border-0 sp-rounded-none sp-z-50 sp-mt-0"
                        placeholder="2"
                        min="2"
                        max="9999"
                        autocomplete="off"
                        value="<?php echo absint($recurringFrequency); ?>"
                    />
                </div>
                <div class="sp-border-0 sp-border-l sp-border-solid sp-border-[#8c8f94] sp-flex-grow">
                    <label for="<?php echo $selectFieldName; ?>" class="screen-reader-text">
                        <?php esc_html_e('Recurring Period', 'stellarpay'); ?>
                    </label>
                    <select
                        id="<?php echo $selectFieldName; ?>" name="<?php echo $selectFieldName; ?>"
                        class="sp-border-0 sp-rounded-none sp-rounded-tr sp-rounded-br sp-mt-0"
                    >
                        <option value="day" <?php selected($recurringPeriod, 'day'); ?>><?php esc_html_e('days', 'stellarpay'); ?></option>
                        <option value="week" <?php selected($recurringPeriod, 'week'); ?>><?php esc_html_e('weeks', 'stellarpay'); ?></option>
                        <option value="month" <?php selected($recurringPeriod, 'month'); ?>><?php esc_html_e('months', 'stellarpay');?></option>
                        <option value="year" <?php selected($recurringPeriod, 'year'); ?>><?php esc_html_e('years', 'stellarpay');?></option>
                    </select>
                </div>
            </div>
        </div>
        <?php
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
