<?php

/**
 * This class is responsible for processing the payment.
 *
 * @package tellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\DataTransferObjects\ProcessPaymentResult;
use StellarPay\Integrations\WooCommerce\ValueObjects\ProcessPaymentResultType;
use StellarPay\Integrations\WooCommerce\Traits\MixedSubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\Stripe\Services\PaymentIntentService;
use StellarPay\PluginSetup\Environment;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\CreateStripeSubscriptionSchedule;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\Stripe\StripeErrorMessage;
use WC_Data_Exception;
use WC_Order;

use function StellarPay\Core\container;

/**
 * @since 1.7.0
 */
class PaymentProcessor
{
    use MixedSubscriptionUtilities;

    /**
     * @since 1.7.0
     */
    private PaymentIntentService $paymentIntentService;

    /**
     * @since 1.7.0
     */
    private ProcessPaymentResult $paymentProcessResult;

    /**
     * @since 1.7.0
     */
    public function __construct(PaymentIntentService $paymentIntentService, ProcessPaymentResult $paymentProcessResult)
    {
        $this->paymentIntentService = $paymentIntentService;
        $this->paymentProcessResult = $paymentProcessResult;
    }

    /**
     * @since 1.7.0
     */
    public function withRedirectUrl(string $redirectUrl): self
    {
        $this->paymentProcessResult->setRedirect($redirectUrl);

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function process(WC_Order $order): ProcessPaymentResult
    {
        try {
            $this->paymentProcessResult->setResult(ProcessPaymentResultType::SUCCESS());


            // We should create payment intent only if the order amount is greater than zero.
            if (!$this->isZeroOrderValue($order)) {
                $this->createStripePaymentIntent($order);
            }

            // Set the order status to "pending".
            // This function also triggers to complete payment of failed orders,
            // so we need to make sure the order is pending.
            $order->update_status('pending');

            // Process StellarPay subscription
            if ($this->hasSubscriptionProduct($order)) {
                $this->processStellarPaySubscription($order);

            // Process WooCommerce subscription.
            } elseif (Environment::isWooSubscriptionActive() && $this->hasWooSubscriptionProduct($order) && $this->isZeroOrderValue($order)) {
                $order->payment_complete();
                $this->paymentProcessResult->subscriptionWithZeroOrderValue();
            }

            return $this->paymentProcessResult;
        } catch (\Exception $e) {
            // @todo - log error.

            return $this->returnError($e);
        }
    }

    /**
     * @since 1.7.0
     */
    private function returnError(\Exception $e): ProcessPaymentResult
    {
        $errorMessage = $e instanceof StripeAPIException
                ? StripeErrorMessage::getErrorMessage($e)
                : $e->getMessage();

        // Legacy Checkout Compatibility: Add an error message to the WooCommerce.
        wc_add_notice($errorMessage, 'error');

        $this->paymentProcessResult->setError('stellarpay-payment-process-error', $errorMessage);

        return $this->paymentProcessResult;
    }

    /**
     * @since 1.7.0
     * @throws StripeAPIException|WC_Data_Exception|Exception|BindingResolutionException
     */
    private function createStripePaymentIntent(WC_Order $order): void
    {
        $paymentIntent =  $this->paymentIntentService->createOrUpdate($order);

        $this->paymentProcessResult->setAdditionalData([
            // We get client secret from the payment intent to confirm the payment.
            'clientSecret' => $paymentIntent->getClientSecret(),

            // We get either saved token id or payment method id (generate by stripe elements) from the client side.
            // We return the payment method id which we linked to payment intent.
            // The Stripe uses it to confirm the payment.
            'paymentMethodId' => $paymentIntent->getPaymentMethod(),
        ]);
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException|Exception
     */
    private function processStellarPaySubscription(WC_Order $order): void
    {
        $subscriptionProcessor = container(StellarPaySubscriptionProcessor::class);
        $areSubscriptionCreated = $subscriptionProcessor->createSubscriptions($order);

        // Immediately create a subscription on the Stripe if the order amount is zero.
        // This is required to create a subscription schedule on the Stripe for the subscription.
        if ($areSubscriptionCreated && $this->isZeroOrderValue($order)) {
            $subscriptions = Subscription::findAllByFirstOrderId($order->get_id());
            $createStripeSubscriptionSchedule = container(CreateStripeSubscriptionSchedule::class);

            foreach ($subscriptions as $subscription) {
                $createStripeSubscriptionSchedule($subscription);
            }

            // Complete the order if the subscription is created on the Stripe
            $order->payment_complete();

            $this->paymentProcessResult->subscriptionWithZeroOrderValue();
        }
    }

    /**
     * @since 1.7.0
     */
    private function isZeroOrderValue(WC_Order $order): bool
    {
        return $order->get_total('edit') <= 0;
    }
}
