<?php

/**
 * HandlesInvoice Trait.
 *
 * This trait is responsible for handling the Stripe invoice related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\StripeClient;
use StellarPay\Vendors\Stripe\Invoice;

/**
 * Trait HandlesInvoice
 *
 * @since 1.4.0
 * @property StripeClient $client
 */
trait HandlesInvoice
{
    /**
     * @since 1.4.0
     * @throws StripeAPIException
     */
    public function getUpcomingInvoiceForSubscription(string $subscriptionId): Invoice
    {
        try {
            $parameters = [
                'subscription' => $subscriptionId,
                'subscription_details' => [
                    'cancel_now' => true,
                ]
            ];

            return $this->client->invoices->upcoming($parameters);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.4.0
     * @throws StripeAPIException
     */
    public function getLastPaidInvoiceForSubscription(string $subscriptionId): ?Invoice
    {
        $parameters = [
            'subscription' => $subscriptionId,
            'status' => 'paid',
            'limit' => 1,
        ];

        try {
            return $this->client->invoices->all($parameters)->first(); // @phpstan-ignore-line
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
