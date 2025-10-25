<?php

/**
 * This trait is used to handle the Stripe price related api request.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PriceDTO;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\Price;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesPrice
 *
 * @since 1.0.0
 * @property-read StripeClient $client
 */
trait HandlesPrice
{
    /**
     * @throws StripeAPIException
     */
    public function createPrice(PriceDTO $priceDTO): Price
    {
        try {
            return $this->client->prices->create($priceDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
