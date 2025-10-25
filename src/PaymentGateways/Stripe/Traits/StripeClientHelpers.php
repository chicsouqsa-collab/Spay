<?php

/**
 * This trait uses to a setup the stripe client based on mode with services.
 *
 * @package StellarPay\PaymentGateways\Stripe\Traits
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\Stripe\Client as StripeClient;
use StellarPay\PaymentGateways\Stripe\Services\ServiceRegisterer;

use function StellarPay\Core\container;

/**
 * @since 1.1.0 Rename trait
 * @since 1.0.0
 */
trait StripeClientHelpers
{
    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    protected function getStripeClient(PaymentGatewayMode $paymentGatewayMode): Client
    {
        return Client::getClient($paymentGatewayMode);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    protected function setStripeClientWithServices(PaymentGatewayMode $paymentGatewayMode): void
    {
        $stripeClient = $this->getStripeClient($paymentGatewayMode);

        container()->singleton(StripeClient::class, function () use ($stripeClient) {
            return $stripeClient;
        });

        container(ServiceRegisterer::class)->register();
    }
}
