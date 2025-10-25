<?php

/**
 * ChargeService
 *
 * This class is responsible for handling the charge service.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\ChargeDTO;

/**
 * Class ChargeService
 *
 * @since 1.4.0
 */
class ChargeService extends StripeApiService
{
    /**
     * @since 1.4.0
     *
     * @return ChargeDTO[]
     */
    public function searchCharges(array $parameters): array
    {
        $charges = $this->httpClient->searchCharges($parameters)->data;

        return array_map(
            function ($charge) {
                return ChargeDTO::fromStripeResponse($charge); // @phpstan-ignore-line
            },
            $charges
        );
    }
}
