<?php

/**
 * This class is used for price related rest api requests for Stripe.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PriceDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PriceDTO as StripeResponsePriceDTO;

/**
 * Class PriceService
 *
 * @since 1.0.0
 */
class PriceService extends StripeApiService
{
    /**
     * This method creates a new price in Stripe.
     *
     * @since 1.0.0
     */
    public function createPrice(PriceDTO $priceDTO): StripeResponsePriceDTO
    {
        return StripeResponsePriceDTO::fromStripeResponse(
            $this->httpClient->createPrice($priceDTO)
        );
    }
}
