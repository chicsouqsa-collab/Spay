<?php

/**
 * This classes us responsible to toggle customer register on the WooCommerce subscription order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;

/**
 * @since 1.0.0
 */
class RegistrationOnCheckoutWithSubscriptionProduct
{
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function requireRegistrationDuringCheckout(bool $accountRequired): bool
    {
        if ($this->isRegistrationRequired()) {
            return true;
        }

        return $accountRequired;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function forceRegistrationDuringCheckout(): void
    {
        if ($this->isRegistrationRequired()) {
            $_POST['createaccount'] = '1';
        }
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function maybeEnableRegistration(bool $registrationEnabled): bool
    {
        return $this->requireRegistrationDuringCheckout($registrationEnabled);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function isRegistrationRequired(): bool
    {
        return ! is_user_logged_in() && $this->cartContainsSubscription();
    }
}
