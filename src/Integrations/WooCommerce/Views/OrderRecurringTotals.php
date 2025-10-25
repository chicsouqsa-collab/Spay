<?php

/**
 * This class uses to display recurring total on the Woocommerce cart and checkout (legacy) page.
 *
 * @package StellarPay\Integrations\WooCommerce\Views
 * @since 1.5.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views;

use DateInterval;
use DateTime;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\InstallmentSubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\SubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\Product;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Core\Constants;
use StellarPay\Integrations\WooCommerce\Utils\PageType;
use WC_Product;

use function StellarPay\Core\container;

/**
 * @since 1.5.0
 */
class OrderRecurringTotals
{
    use SubscriptionUtilities;

    /**
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        $formattedSubscriptionProductsList = $this->getSubscriptionListData();

        if (empty($formattedSubscriptionProductsList)) {
            return;
        }

        $html = '<tr><th colspan="2">' . esc_html__('Recurring Total', 'stellarpay') . '<tr></tr>';

        foreach ($formattedSubscriptionProductsList as $listItem) {
            $html .= sprintf(
                '<tr class="cart-subtotal sp-subscription-order-summary--classic">
                    <th>
                        <span class="sp-subscription-order-summary__title">%1$s</span>
                        %2$s
                        <small class="sp-subscription-order-summary__frequency">%3$s</small>
                    </th>
                    <td>
                        <span class="sp-subscription-order-summary__amount">%4$s</span>
                        <small class="sp-subscription-order-summary__price-subtitle">%5$s</small>
                    </td>
                </tr>',
                $listItem['product_title'],
                $listItem['type']->getValue() === 'installmentPayments' ? '<small class="sp-subscription-order-summary__recurring-total">' . $listItem['formatted_frequency'] . '</small>' : '',
                $listItem['formatted_renewal_date'],
                wc_price($listItem['amount']),
                $listItem['type']->getValue() === 'installmentPayments' ? $listItem['formatted_installments'] : $listItem['frequency_adverb']
            );
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @since 1.8.0 Use order item regular amount.
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function getSubscriptionListData(): ?array
    {
        $subscriptionProducts = $this->getSubscriptionProductsFromCart();

        if (empty($subscriptionProducts)) {
            return null;
        }

        $formattedSubscriptionProductsList = [];
        foreach ($subscriptionProducts as $subscriptionProduct) {
            if (! $subscriptionProduct->product instanceof WC_Product || empty($subscriptionProduct->quantity)) {
                continue;
            }

            $product = ProductFactory::makeFromProduct($subscriptionProduct->product);
            if (! $product instanceof SubscriptionProduct) {
                continue;
            }

            $productAmount = (float)$product->getRegularAmount('edit');
            $arrayKey = sprintf('%d_%s_%d_%s', $product->get_id(), $product->getPeriod()->getValue(), $product->getFrequency(), $product->getProductType()->getValue());

            // Increase the amount if the subscription already exists for a similar period and frequency.
            if (! empty($formattedSubscriptionProductsList[$arrayKey])) {
                $formattedSubscriptionProductsList[$arrayKey]['amount'] += $productAmount;
                continue;
            }

            $productType = $product->getProductType();
            $productFrequency = $product->getFrequency();
            $productPeriod = $product->getPeriod();
            $firstRenewalDate = $this->calculateFirstRenewalDate($productPeriod, $productFrequency);
            $totalAmount = $productAmount * $subscriptionProduct->quantity;

            $formattedSubscriptionProductsList[$arrayKey] = [
                'product_id' => $product->get_id(),
                'product_title' => $product->get_name(),
                'period'  => $productPeriod,
                'type'  => $productType,
                'frequency' => $productFrequency,
                'amount'  => $totalAmount,
                'startDate' => Temporal::getWPFormattedDate($firstRenewalDate),
                'frequency_adverb' => $productPeriod->getFormattedAdverbLabelByFrequency($productFrequency),
                'formatted_frequency' => $this->getFormattedFrequency($product, $totalAmount),
                'formatted_renewal_date' => $this->getFormattedRenewalDate($firstRenewalDate),
                'formatted_installments' => $this->getFormattedInstallments($product),
            ];
        }

        return $formattedSubscriptionProductsList;
    }

    /**
     * @since 1.8.0 Change the type of parameter $product to SubscriptionProduct.
     * @since 1.5.0
     */
    private function getFormattedFrequency(SubscriptionProduct $product, float $totalAmount): string
    {
        $productFrequency = $product->getFrequency();
        $productPeriod = $product->getPeriod();

        return sprintf(
            /* translators: Subscription period label. Example: daily, weekly, monthly, yearly */
            esc_html__('Recurring %1$s', 'stellarpay'),
            $productPeriod->getAdverbLabelByFrequency($productFrequency),
        );
    }

    /**
     * Example: $9.99 monthly for 12 months
     *
     * @since 1.8.0 Change the type of parameter $product to SubscriptionProduct and Add asterisk to the price HTML string.
     * @since 1.5.0
     */
    public function getPriceWithFormattedFrequencyForCartItem(SubscriptionProduct $product, float $totalAmount, string $type = 'classic'): string
    {
        $productFrequency = $product->getFrequency();
        $productPeriod = $product->getPeriod();

        $priceWithFormattedFrequency = sprintf(
            // Example: $9.99 monthly
            // translators: 1: Subscription amount. 2: Subscription period label. Example: $9.99 monthly
            esc_html__('%1$s %2$s', 'stellarpay'),
            'classic' === $type ? wp_strip_all_tags(wc_price($totalAmount)) : '<price/>',
            $productPeriod->getAdverbLabelByFrequency($productFrequency),
        );

        if ($product instanceof InstallmentSubscriptionProduct) {
            $priceWithFormattedFrequency = sprintf(
                // Example: $9.99 monthly for 12 months
                // translators: 1: Subscription amount. 2: Subscription period label. 3: Subscription duration. Example: $9.99 monthly for 12 months
                esc_html__('%1$s %2$s × %3$s', 'stellarpay'),
                'classic' === $type ? wp_strip_all_tags(wc_price($totalAmount)) : '<price/>',
                $productPeriod->getAdverbLabelByFrequency($productFrequency),
                $product->getNumberOfPayments('edit')
            );
        }

        return sprintf(
            '%1$s%2$s',
            $priceWithFormattedFrequency,
            $product->isOnSale() ? '*' : ''
        );
    }

    /**
     * @since 1.5.0
     */
    private function calculateFirstRenewalDate(SubscriptionPeriod $period, int $frequency): DateTime
    {
        $firstRenewalDate = Temporal::getCurrentDateTime();
        $intervalString = sprintf('+ %d %ss', $frequency, $period);

        $interval = DateInterval::createFromDateString($intervalString);
        $firstRenewalDate->add($interval);

        return $firstRenewalDate;
    }

    /**
     * @since 1.6.0
     */
    public function getFormattedRenewalDate(DateTime $date): string
    {
        return sprintf(
            /* translators: Next renewal date */
            esc_html__('Next renewal %s', 'stellarpay'),
            Temporal::getWPFormattedDate($date),
        );
    }

    /**
     * @since 1.7.0 use non static methods od PageType class.
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function classicCheckoutStyle(): void
    {
        $pageType = container(PageType::class);
        if (! $pageType->isClassicCheckout() && ! $pageType->isClassicCart()) {
            return;
        }

        $scriptId = 'stellarpay-subscription-order-summary-style';
        wp_register_style($scriptId, false, [], Constants::VERSION);
        wp_enqueue_style($scriptId);

        $style = '
        .sp-subscription-order-summary__frequency,
        .sp-subscription-order-summary__recurring-total {
            display: block;
            font-weight: normal;
        }

        .sp-subscription-cart-item__frequency {
            display: block;
        }

        .sp-subscription-order-summary__price-subtitle {
            display: block;
            line-height: 1;
        }
        ';

        wp_add_inline_style($scriptId, $style);
    }

    /**
     * Add subscription summary to cart item name (classic cart).
     *
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function addSubscriptionSummaryToCartItemName($cartItem): void
    {
        if (empty($cartItem['data'])) {
            return;
        }

        $product = ProductFactory::makeFromProduct($cartItem['data']);
        if ($product instanceof SubscriptionProduct) {
            echo sprintf(
                '<div class="sp-subscription-cart-item">
            <small class="sp-subscription-cart-item__frequency">%1$s</small>
            </div>',
                $product->get_price_html('view') // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            );
        }
    }

    /**
     * @since 1.6.0
     */
    private function getFormattedInstallments(Product $product): string
    {
        if ($product instanceof InstallmentSubscriptionProduct) {
            return sprintf(
                /* translators: 1: Number of payments */
                esc_html__('%d payments', 'stellarpay'),
                $product->getNumberOfPayments('edit')
            );
        }

        return '';
    }
}
