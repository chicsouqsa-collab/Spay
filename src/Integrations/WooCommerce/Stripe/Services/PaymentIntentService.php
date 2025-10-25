<?php

/**
 * This class is responsible to provide logic to create or update the Stripe payment intent for the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use StellarPay\Core\ArraySet;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\PaymentIntentDataStrategy;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\PaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentIntentDTO as StripeResponsePaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\Services\PaymentIntentService as BasePaymentIntentService;
use WC_Data_Exception;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class PaymentIntentService
{
    /**
     * @since 1.0.0
     */
    protected BasePaymentIntentService $paymentIntentService;

    /**
     * @since 1.0.0
     *
     * @param BasePaymentIntentService $paymentIntentService
     */
    public function __construct(BasePaymentIntentService $paymentIntentService)
    {
        $this->paymentIntentService = $paymentIntentService;
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException|WC_Data_Exception|Exception
     * @throws BindingResolutionException
     */
    public function createOrUpdate(WC_Order $order): StripeResponsePaymentIntentDTO
    {
        $paymentIntentId = $order->get_transaction_id();

        if ($paymentIntentId) {
            $paymentIntentDTO = $this->paymentIntentService->getPaymentIntent($paymentIntentId);

            $paymentIntent = $this->paymentIntentService->getPaymentIntent($paymentIntentId);
            $activePaymentGatewayMode = container(SettingRepository::class)->getPaymentGatewayMode();

            // If the payment intent is canceled, create a new one.
            if (
                $paymentIntent->isCanceled()
                || $paymentIntent->getPaymentGatewayMode()->notMatch($activePaymentGatewayMode)
            ) {
                return $this->create($order);
            }

            // If the payment intent succeeded, throw an exception.
            // This is to prevent the user from making a payment on already successful payment intent.
            if ($paymentIntent->isSucceeded()) {
                throw new Exception(
                    esc_html__(
                        'The payment process has already been successfully completed. Please contact the site administrator for further assistance.',
                        'stellarpay'
                    )
                );
            }

            // If the payment intent data has changed, update the payment intent.
            return $this->update($order, $paymentIntentDTO) ?? $paymentIntentDTO;
        }

        return $this->create($order);
    }

    /**
     * This method creates a new the Stripe payment intent for a given order.
     *
     * @since 1.0.0
     * @throws StripeAPIException|WC_Data_Exception|BindingResolutionException
     */
    protected function create(WC_Order $order): StripeResponsePaymentIntentDTO
    {
        $paymentIntent = $this->paymentIntentService->createPaymentIntent(
            $this->getPaymentIntentDto($order)
        );

        $order->set_transaction_id($paymentIntent->getId());
        $order->save();

        return $paymentIntent;
    }

    /**
     * This method updates the payment intent.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function update(WC_Order $order, StripeResponsePaymentIntentDTO $paymentIntent): ?StripeResponsePaymentIntentDTO
    {
        $newPaymentIntentDto = $this->getPaymentIntentDto($order);
        $existingPaymentIntentData = $paymentIntent->getStripeResponseAsArray();
        $newPaymentIntentData = $newPaymentIntentDto->toArray();

        $changedData = ArraySet::diffOnCommonKeys($newPaymentIntentData, $existingPaymentIntentData, true);
        $changedData = array_merge($changedData, array_diff_key($newPaymentIntentData, $existingPaymentIntentData));

        // If the payment intent data has changed, update the payment intent.
        if ($changedData) {
            return $this->paymentIntentService->updatePaymentIntent($paymentIntent->getId(), $changedData);
        }

        return null;
    }

    /**
     * This method returns the payment intent data.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function getPaymentIntentDto(WC_Order $order): PaymentIntentDto
    {
        $paymentIntentDataStrategy = container(PaymentIntentDataStrategy::class);
        $paymentIntentDataStrategy->setOrder($order);

        return PaymentIntentDto::fromCustomerDataStrategy($paymentIntentDataStrategy);
    }
}
