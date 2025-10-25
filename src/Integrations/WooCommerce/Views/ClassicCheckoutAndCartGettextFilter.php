<?php

/**
 * All functions related to the classic checkout page and cart page.
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;

/**
 * @since 1.5.0
 */
class ClassicCheckoutAndCartGettextFilter
{
    use SubscriptionUtilities;

    /**
     * @since 1.5.0
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        $subscriptionProducts = $this->getSubscriptionProductsFromCart();

        if (empty($subscriptionProducts)) {
            return;
        }

        Hooks::addFilter('gettext_woocommerce', __CLASS__, 'prependDueTodayLabelToCheckoutTotalLabel', 10, 3);
    }

    /**
     * @since 1.5.0
     */
    public function prependDueTodayLabelToCheckoutTotalLabel(string $label, string $context, string $originalLabel): string
    {
        if ('Total' === $label) {
            return esc_html__('Total due today', 'stellarpay');
        }

        return $label;
    }
}
