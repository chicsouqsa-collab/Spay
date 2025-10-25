<?php

/**
 * This class is used to represent an invoice object from Stripe.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses;

use StellarPay\Vendors\Stripe\Invoice as StripeInvoice;

/**
 * Class InvoiceDTO
 *
 * @since 1.4.0
 */
class InvoiceDTO
{
    /**
     * Stripe payment intent id.
     *
     * @since 1.4.0
     */
    private string $subscription;

    /**
     * ID of the latest charge generated.
     *
     * @since 1.4.0
     */
    private ?string $chargeId;

    /**
     * Stripe payment intent amount.
     *
     * @since 1.4.0
     */
    private int $total;

    /**
     * Create a new Invoice instance from a Stripe response.
     *
     * @since 1.4.0
     */
    public static function fromStripeResponse(StripeInvoice $response): self
    {
        $invoice = new self();

        $invoice->subscription = $response->subscription;
        $invoice->chargeId = $response->charge;
        $invoice->total = $response->total;

        return $invoice;
    }

    /**
     * Get the invoice subscription.
     *
     * @since 1.4.0
     */
    public function getSubscription(): string
    {
        return $this->subscription;
    }

    /**
     * Get the invoice total.
     *
     * @since 1.4.0
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Get the charge ID.
     *
     * @since 1.4.0
     */
    public function getChargeId(): ?string
    {
        return $this->chargeId;
    }
}
