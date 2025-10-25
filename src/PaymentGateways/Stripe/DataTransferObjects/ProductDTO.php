<?php

/**
 * This class is transfer data for the Stripe product related rest api requests.
 *
 * @package StellarPay/PaymentGateways/Stripe/Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Class ProductDTO
 *
 * @since 1.0.0
 */
class ProductDTO
{
    /**
     * @since 1.0.0
     */
    public ?string $name = null;

    /**
     * @since 1.0.0
     */
    public ?string $description = null;

    /**
     * @since 1.0.0
     */
    public ?string $url = null;

    /**
     * @since 1.0.0
     */
    private ?array $dataFromStrategy = null;

    /**
     * Create a new PaymentIntent instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromProductDataStrategy(DataStrategy $dataStrategy): self
    {
        $paymentIntent = new self();

        $paymentIntent->dataFromStrategy = $dataStrategy->generateData();

        return $paymentIntent;
    }

    /**
     * This function return results which compatible with the Stripe API.
     * You can check payment intent object in Stripe API documentation for more information.
     * https://docs.stripe.com/api/products/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->dataFromStrategy) {
            $data = $this->dataFromStrategy;
        }

        if ($this->name) {
            $data['name'] = $this->name;
        }

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->url) {
            $data['url'] = $this->url;
        }

        return $data;
    }
}
