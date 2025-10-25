<?php

/**
 * Refund Model.
 *
 * This class is responsible manage Stripe refund.
 *
 * @package StellarPay/PaymentGateways\Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Core\ValueObjects\Money;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\Traits\RefundTrait;
use StellarPay\Vendors\Stripe\Refund as StripeRefund;

/**
 * Class Refund.
 *
 * @since 1.0.0
 */
class RefundDTO
{
    use RefundTrait;

    /**
     * The Stripe refund object.
     *
     * @since 1.0.0
     */
    private StripeRefund $stripeResponse;

    /**
     * Create a new Refund instance from a Stripe response.
     *
     * @since 1.0.0
     */
    public static function fromStripeResponse(StripeRefund $response): self
    {
        $refund = new self();

        $refund->stripeResponse = $response;

        return $refund;
    }

    /**
     * Get the refund ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->stripeResponse->id;
    }

    /**
     * Get the refund status.
     *
     * @return string
     */
    public function getRefundStatus(): string
    {
        return $this->stripeResponse->status;
    }

    /**
     * Get the refund minor amount (in cents).
     *
     * @since 1.4.0
     */
    public function getRefundMinorAmount(): int
    {
        return $this->stripeResponse->amount;
    }

    /**
     * Get the refund currency.
     *
     * @since 1.4.0
     */
    public function getRefundCurrency(): string
    {
        return $this->stripeResponse->currency;
    }

    /**
     * @since 1.4.0
     */
    public function getFormattedAmount(): string
    {
        $amount = Money::fromMinorAmount($this->getRefundMinorAmount(), $this->getRefundCurrency());

        return wc_price($amount->getAmount(), ['currency' => $this->getRefundCurrency()]);
    }
}
