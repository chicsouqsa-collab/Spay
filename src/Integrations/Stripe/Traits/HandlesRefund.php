<?php

/**
 * HandlesRefund Trait.
 *
 * This trait is responsible for handling the Stripe refund related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\RefundDTO;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\Refund;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesRefund
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesRefund
{
    /**
     * This method refund payment.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createRefund(RefundDTO $refundDTO): Refund
    {
        try {
            return $this->client->refunds->create($refundDTO->toArray());
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @throws StripeAPIException
     */
    public function getLatestRefundByPaymentIntentId(string $paymentIntentId): ?Refund
    {
        try {
            return $this->client->refunds->all(['payment_intent' => $paymentIntentId, 'limit' => 1])->first(); // @phpstan-ignore-line
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
