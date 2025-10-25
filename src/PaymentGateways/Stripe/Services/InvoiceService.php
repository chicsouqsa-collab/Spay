<?php

/**
 * InvoiceService
 *
 * This class is responsible for handling the invoice service.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\InvoiceDTO as StripeResponseInvoiceDTO;

/**
 * Class InvoiceService
 *
 * @since 1.4.0
 */
class InvoiceService extends StripeApiService
{
    /**
     * Get upcoming invoice.
     *
     * @since 1.4.0
     */
    public function getUpcomingInvoiceForSubscription(string $subscriptionId): StripeResponseInvoiceDTO
    {
        return StripeResponseInvoiceDTO::fromStripeResponse(
            $this->httpClient->getUpcomingInvoiceForSubscription($subscriptionId)
        );
    }

    /**
     * @since 1.4.0
     */
    public function getLastPaidInvoiceForSubscription(string $subscriptionId): ?StripeResponseInvoiceDTO
    {
        $invoice = $this->httpClient->getLastPaidInvoiceForSubscription($subscriptionId);

        return $invoice ? StripeResponseInvoiceDTO::fromStripeResponse($invoice) : null;
    }
}
