<?php

/**
 * PaymentIntent Model
 *
 * This class is responsible for managing payment intent data for the Stripe payment gateway.
 * This class is used to create a payment intent for Stripe using WooCommerce order data.
 * In the future, Payment intent data can be generated from other sources as well.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe/DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests;

use StellarPay\Core\Contracts\DataStrategy;

/**
 * Class PaymentIntent
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\DataTransferObjects
 * @since 1.0.0
 */
class PaymentIntentDTO
{
    /**
     * The amount to charge.
     *
     * Note: amount is in cents.
     *
     * @since 1.0.0
     */
    public int $amount;

    /**
     * The currency to charge.
     *
     * @since 1.0.0
     */
    public string $currency;

    /**
     * @since 1.0.0
     */
    public array $dataFromStrategy;

    /**
     * Create a new PaymentIntent instance from a WooCommerce order.
     *
     * @since 1.0.0
     */
    public static function fromCustomerDataStrategy(DataStrategy $dataStrategy): PaymentIntentDTO
    {
        $paymentIntent = new self();

        $paymentIntent->dataFromStrategy = $dataStrategy->generateData();
        $paymentIntent->amount = $paymentIntent->dataFromStrategy['amount'];
        $paymentIntent->currency = $paymentIntent->dataFromStrategy['currency'];

        return $paymentIntent;
    }

    /**
     * Set the amount to charge.
     *
     * This function returns results which are compatible with the Stripe API.
     * You can check a payment intent object in Stripe API documentation for more information.
     * Link - https://docs.stripe.com/api/payment_intents/object
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $data = $this->dataFromStrategy;
        $data['amount'] = $this->amount;
        $data['currency'] = $this->currency;

        return $data;
    }
}
