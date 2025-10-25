<?php

/**
 * Refund Data Transfer Object.
 *
 * This class is responsible to manage the refund data transfer object.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Refund Data Transfer Object.
 *
 * This class is responsible to manage the refund data transfer object.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */
class RefundDTO
{
    /**
     * The payment intent ID.
     *
     * @since 1.4.0 `paymentIntentId` can be null.
     * @since 1.0.0
     */
    public ?string $paymentIntentId;

    /**
     * The payment intent ID.
     *
     * @since 1.0.0
     */
    public ?string $chargeId;

    /**
     * The amount to refund.
     *
     * Note: amount is in cents.
     *
     * @since 1.0.0
     */
    public int $amount;

    /**
     * The reason for the refund.
     *
     * @since 1.0.0
     */
    public string $reason = '';

    public ?array $metadata = [];

    /**
     * @since 1.0.0
     */
    public array $dataFromStrategy;

    /**
     * Create a new PaymentIntent instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromCustomerDataStrategy(DataStrategy $dataStrategy): self
    {
        $refund = new self();

        $refund->dataFromStrategy = $dataStrategy->generateData();
        $refund->paymentIntentId = $refund->dataFromStrategy['payment_intent'];
        $refund->amount = $refund->dataFromStrategy['amount'];

        if (array_key_exists('reason', $refund->dataFromStrategy)) {
            $refund->reason = $refund->dataFromStrategy['reason'];
        }

        return $refund;
    }

    /**
     * @since 1.4.0
     */
    public static function fromArray(array $array): self
    {
        $refund = new self();

        $refund->paymentIntentId = $array['paymentIntentId'] ?? null;
        $refund->chargeId = $array['chargeId'] ?? null;
        $refund->amount = $array['amount'] ?? 0;
        $refund->reason = $array['reason'] ?? '';
        $refund->metadata = $array['metadata'] ?? null;

        return $refund;
    }

    /**
     * Convert the object to an array.
     *
     * This function returns results which are compatible with the Stripe API.
     * You can check create a refund object in Stripe API documentation for more information.
     * Link - https://docs.stripe.com/api/refunds/create
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = $this->dataFromStrategy ?? [];
        $data['payment_intent'] = $this->paymentIntentId;
        $data['amount'] = $this->amount;
        $data['charge'] = $this->chargeId ?? null;
        $data['reason'] = $this->reason ?? null;
        $data['metadata'] = $this->metadata ?? null;

        return array_filter($data);
    }
}
