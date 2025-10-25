<?php

/**
 * This class is used to represent a charge object from Stripe.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Charge as StripeCharge;

/**
 * Class ChargeDTO
 *
 * @since 1.4.0
 */
class ChargeDTO
{
    /**
     * Stripe charge id.
     *
     * @since 1.4.0
     */
    private string $id;

    /**
     * @since 1.4.0
     */
    private int $amount;

    /**
     * @since 1.4.0
     */
    private string $currency;

    /**
     * @since 1.4.0
     */
    private bool $refunded;

    /**
     * @since 1.4.0
     */
    private string $status;

    /**
     * Create a new Invoice instance from a Stripe response.
     *
     * @since 1.4.0
     */
    public static function fromStripeResponse(StripeCharge $response): self
    {
        $invoice = new self();

        $invoice->id = $response->id;
        $invoice->amount = $response->amount;
        $invoice->currency = $response->currency;
        $invoice->refunded = $response->refunded;
        $invoice->status = $response->status;

        return $invoice;
    }

    /**
     * @since 1.4.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @since 1.4.0
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @since 1.4.0
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @since 1.4.0
     */
    public function getRefunded(): bool
    {
        return $this->refunded;
    }

    /**
     * @since 1.4.0
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
