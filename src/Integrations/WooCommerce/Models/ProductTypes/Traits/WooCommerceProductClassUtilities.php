<?php

/**
 * This class has common functions that are required when extend Woocommerce Product class.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;

/**
 * @since 1.8.0
 *
 * @method string getFormattedBillingPeriod()
 * @method string getRegularAmount(string $context = 'view')
 * @method string getSaleAmount(string $context = 'view')
 */
trait WooCommerceProductClassUtilities
{
    /**
     * @since 1.8.0
     */
    public function get_regular_price($context = 'view'): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->getRegularAmount($context);
    }

    /**
     * @since 1.8.0
     */
    public function get_sale_price($context = 'view'): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->getSaleAmount($context);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function get_price_html($deprecated = ''): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ('' === $this->get_price()) {
            $price = apply_filters('woocommerce_empty_price_html', '', $this);
        } elseif ($this->is_on_sale()) {
            $price = wc_format_sale_price(
                (string) wc_get_price_to_display(
                    $this,
                    [ 'price' => $this->get_regular_price() ]
                ),
                (string) wc_get_price_to_display($this)
            ) . $this->get_price_suffix();
        } else {
            $price = wc_price(wc_get_price_to_display($this)) . $this->get_price_suffix();
        }

        return sprintf(
            // translators: %1$s is the amount, %2$s is the billing period. Example: `$10 / 3 months`.
            _x('%1$s / %2$s%3$s', 'Subscription product price', 'stellarpay'),
            $price,
            $this->getFormattedBillingPeriod(),
            $this->isOnSale() ? '*' : ''
        );
    }
}
