<?php

/**
 * This class is responsible to add subscription sale price related notice.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\ProductEditPage
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\ProductEditPage;

use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\Traits\WooCommercePageUtilities;
use WC_Product;

/**
 * @since 1.8.0
 */
class SubscriptionSalePriceNotice
{
    use SubscriptionUtilities;
    use WooCommercePageUtilities;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function invokeOnProductPage(): void
    {
        global $product;

        if (! $product instanceof WC_Product) {
            return;
        }

        if (! $product->is_on_sale('edit')) {
            return;
        }

        $SPProduct = ProductFactory::makeFromProduct($product);

        if (! $SPProduct instanceof SubscriptionProduct) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        printf('<span>%1$s</span>', $this->getNotice());
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function invokeOnLegacyCartPage(): void
    {
        if ($this->hasAtLeastOneProductOnSaleInCart()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            printf('<tr><td colspan="6" style="padding-left: 0; background-color: transparent">%1$s</td></tr>', $this->getNotice());
        }
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function invokeOnLegacyCheckoutPage(): void
    {
        if ($this->hasAtLeastOneProductOnSaleInCart()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            printf('<tr style="font-size: %2$s"><td colspan="2" style="padding-left: 0; background-color: transparent">%1$s</td></tr>', $this->getNotice(), $this->getMessageFontSize());
        }
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function invokeOnBlockCartPage(): void
    {
        if (! $this->isBlockCartPage() || ! $this->hasAtLeastOneProductOnSaleInCart()) {
            return;
        }

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        $notice = $this->getNotice();
        $fontSize = $this->getMessageFontSize();
        $script = "
            document.addEventListener('DOMContentLoaded', () => {
                const interval = setInterval(() => {
                    const cartItemsBlock = document.querySelector('div.wp-block-woocommerce-cart-items-block');

                    if (cartItemsBlock) {
                        const table = cartItemsBlock.querySelector('table.wc-block-cart-items');
                        if (table) {
                            clearInterval(interval);

                            const newRow = document.createElement('tr');
                            const newCell = document.createElement('td');
                            newCell.setAttribute('colspan', '3');
                            newCell.textContent = '$notice';
                            newCell.style.paddingLeft = '0';
                            newCell.style.fontSize = '$fontSize';

                            newRow.appendChild(newCell);
                            table.querySelector('tbody').appendChild(newRow);
                        }
                    }
                }, 100);

                // Clear the interval after 10 seconds to prevent infinite checking
                setTimeout(() => clearInterval(interval), 10000);
            });
        ";
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

        EnqueueScript::addInlineScript('stellarpay-subscription-sale-price-cart-notice', $script);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function invokeOnBlockCheckoutPage(): void
    {
        if (! $this->isBlockCheckoutPage() || ! $this->hasAtLeastOneProductOnSaleInCart()) {
            return;
        }

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        $notice = $this->getNotice();
        $fontSize = $this->getMessageFontSize();
        $script = "
            document.addEventListener('DOMContentLoaded', () => {
                const interval = setInterval(() => {
                    const summaryBlock = document.querySelector('div.wp-block-woocommerce-checkout-order-summary-block');

                    if (summaryBlock) {
                        clearInterval(interval);

                        const newDiv = document.createElement('div');
                        newDiv.textContent = '$notice';
                        newDiv.style.marginTop = '5px';
                        newDiv.style.fontSize = '$fontSize';

                        summaryBlock.after(newDiv);
                    }
                }, 100);

                // Clear the interval after 10 seconds to prevent infinite checking
                setTimeout(() => clearInterval(interval), 10000);
            });
       ";
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

        EnqueueScript::addInlineScript('stellarpay-subscription-sale-price-checkout-notice', $script);
    }

    /**
     * @since 1.8.0
     */
    private function getNotice(): string
    {
        return sprintf(
            /* translators: 1: Subscription sale price notice */
            '*%1$s',
            esc_html__('Sale price only applies to the first payment.', 'stellarpay')
        );
    }

    /**
     * @since 1.8.0
     */
    private function getMessageFontSize(): string
    {
        return '0.875em';
    }
}
