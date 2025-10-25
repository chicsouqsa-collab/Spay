<?php

/**
 * This class is used to manage the price data for the Stripe rest api requests.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Class PriceDTO
 *
 * @since 1.0.0
 */
class PriceDTO
{
    /**
     * @var array
     */
    private array $dataFromStrategy;

    /**
     * This function generates data for the Stripe price.
     *
     * @since 1.0.0
     */
    public static function fromPriceDataStrategy(DataStrategy $dataStrategy): self
    {
        $price = new self();
        $price->dataFromStrategy = $dataStrategy->generateData();

        return $price;
    }

    /**
     * This function return results which compatible with the Stripe API.
     * You can check a price object in Stripe API documentation for more information.
     * https://docs.stripe.com/api/prices/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return $this->dataFromStrategy;
    }
}
