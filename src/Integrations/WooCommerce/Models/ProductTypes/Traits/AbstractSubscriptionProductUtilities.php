<?php

/**
 * This class is an abstract trait for StellarPay product types that support subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;

/**
 * @since 1.8.0
 */
trait AbstractSubscriptionProductUtilities
{
    /**
     * @since 1.8.0
     */
    abstract public function getBillingPeriod($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    abstract public function getRecurringPeriod($context = 'view'): string;

    /**
     * @since 1.8.0
     */
    abstract public function getRecurringFrequency($context = 'view'): int;

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getPeriod(): SubscriptionPeriod
    {
        $billingPeriod = $this->getBillingPeriod('edit');

        if ('custom' === $billingPeriod) {
            $billingPeriod = $this->getRecurringPeriod('edit');
        }

        return SubscriptionPeriod::from($billingPeriod);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getFrequency(): int
    {
        if ('custom' !== $this->getBillingPeriod('edit')) {
            $frequency = 1;
        } else {
            $frequency = $this->getRecurringFrequency('edit');
        }

        return $frequency;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public function getFormattedBillingPeriod(): string
    {
        $billingPeriod = $this->getBillingPeriod();

        if ('custom' !== $billingPeriod) {
            return $billingPeriod;
        }

        $frequency       = $this->getRecurringFrequency('edit');
        $period          = $this->getRecurringPeriod('edit');
        $billingPeriod   = SubscriptionPeriod::from($period);
        $formattedPeriod = $billingPeriod->getLabelByFrequency($frequency);

        return sprintf(
        // translators: %1$s is the frequency, %2$s is the period. Example: `3 months`.
            _x('%1$s %2$s', 'Subscription item billing period', 'stellarpay'),
            $frequency,
            $formattedPeriod
        );
    }
}
