<?php

/**
 * HandlesCharge Trait.
 *
 * This trait is responsible for handling the Stripe charge related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;
use StellarPay\Vendors\Stripe\SearchResult;

/**
 * Trait HandlesCharge
 *
 * @since 1.4.0
 * @property StripeClient $client
 */
trait HandlesCharge
{
    /**
     * @since 1.4.0
     */
    public function searchCharges(array $parameters): SearchResult
    {
        try {
            return $this->client->charges->search($parameters);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
