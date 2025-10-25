<?php

/**
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions;

use Exception;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\PaymentIntentDataStrategy;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\PaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.7.0
 */
class ProcessRenewal
{
    /**
     * @since 1.7.0
     */
    public function __invoke(float $amount, WC_Order $renewalOrder): void
    {
        try {
            $paymentIntentDataStrategy = container(PaymentIntentDataStrategy::class);
            $paymentIntentDataStrategy->setOrder($renewalOrder)
                ->offSessionPayment();

            $paymentIntentDTO = PaymentIntentDTO::fromCustomerDataStrategy($paymentIntentDataStrategy);

            $paymentIntent = container(PaymentIntentService::class)->createPaymentIntent($paymentIntentDTO);

            if ($paymentIntent->getStatus()->isSucceeded()) {
                $renewalOrder->payment_complete($paymentIntent->getId());
            } else {
                $renewalOrder->set_transaction_id($paymentIntent->getId());
                $renewalOrder->save();
            }
        } catch (Exception $exception) {
            $renewalOrder->update_status(OrderStatus::FAILED);

            $baseErrorMessage = $exception instanceof StripeAPIException
                /* translators: 1: Error message */
                ? esc_html__('Payment failed due to a Stripe error: %1$s', 'stellarpay')
                /* translators: 1: Error message */
                : esc_html__('Payment failed due to an error: %1$s', 'stellarpay');

            $renewalOrder->add_order_note(sprintf($baseErrorMessage, $exception->getMessage()));
        }
    }
}
