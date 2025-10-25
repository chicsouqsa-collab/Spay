<?php

/**
 * This class is responsible for editing the payment gateways availability on checkout.
 * Customer can check out with the StellaPay payment gateway only for subscription product.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use WC_Cart;

/**
 * @since 1.9.0
 */
final class EditPaymentGatewaysAvailabilityOnCheckout
{
    use SubscriptionUtilities;

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    public function __invoke(array $paymentGateway): array
    {
        if (wc()->cart instanceof WC_Cart && $this->cartContainsSubscription()) {
            return array_filter($paymentGateway, static function ($gateway) {
                return Constants::GATEWAY_ID === $gateway->id;
            });
        }

        return $paymentGateway;
    }
}
