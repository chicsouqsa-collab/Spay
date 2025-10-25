<?php

/**
 * Stripe API Service.
 *
 * This abstract class is used to extend by the Stripe API services.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Integrations\Stripe\Contracts\PaymentGatewayInterface;

/**
 * Class StripeApiService
 *
 * @since 1.0.0
 */
abstract class StripeApiService
{
    /**
     * @since 1.0.0
     */
    protected PaymentGatewayInterface $httpClient;

    /**
     * StripeApiService constructor.
     *
     * @since 1.0.0
     * @param PaymentGatewayInterface $httpClient
     */
    public function __construct(PaymentGatewayInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Set the HTTP client.
     *
     * This function is used to set the HTTP client.
     * When plugin bootstrap, the Stripe client loads either the live or test mode.
     * In few scenarios, the Stripe client needs to be set manually regardless of active mode in the settings.
     * For example, when deleting a webhook, the Stripe client needs to be set to mode, set in api parameters.
     *
     * @since 1.0.0
     * @param PaymentGatewayInterface $httpClient
     */
    public function setHttpClient(PaymentGatewayInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }
}
