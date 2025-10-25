<?php

/**
 * RefundService
 *
 * This class is responsible for handling the refund service.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\RefundDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\RefundDTO as StripeResponseRefundDTO;

/**
 * Class RefundService
 *
 * @since 1.0.0
 */
class RefundService extends StripeApiService
{
    /**
     * This function creates a Stripe refund.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createRefund(RefundDTO $refund): StripeResponseRefundDTO
    {
        return StripeResponseRefundDTO::fromStripeResponse(
            $this->httpClient->createRefund($refund)
        );
    }
}
