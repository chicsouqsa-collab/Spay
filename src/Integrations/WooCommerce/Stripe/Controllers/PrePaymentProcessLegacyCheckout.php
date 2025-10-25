<?php

/**
 * This class is responsible for processing a few things which required before payment on legacy checkout page.
 * For example,
 * - creating the Stripe customer
 * - attaching a payment method to a customer.
 *       Attaching a payment method to a customer helps to charge the customer in the future.
 *       Customer will be charge with the same payment method instead of default payment method.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use WC_Order;
use WC_Payment_Token;
use WC_Payment_Tokens;

/**
 * Class PrePaymentProcessLegacyCheckout
 *
 * @since 1.8.0 Use "hasOneOfSubscriptionTypeInTheOrder" function
 * @since 1.0.0
 */
class PrePaymentProcessLegacyCheckout extends PrePaymentProcessor
{
    /**
     * @since 1.0.0
     * @throws StripeAPIException|Exception
     * @throws BindingResolutionException
     */
    public function __invoke(PaymentContext $paymentContext): void
    {
        $order = $paymentContext->order; // @phpstan-ignore-line
        $paymentMethodId = $order->get_payment_method('edit');

        // If the payment method is not ours, then return.
        if (Constants::GATEWAY_ID !== $paymentMethodId) {
            return;
        }

        $postedData = $this->request->sanitize($this->request->all());

        // Set order payment gateway mode.
        $this->orderRepository->setPaymentGatewayMode($order, $this->settingRepository->getPaymentGatewayMode());

        // Create or get the Stripe customer and attach it to the order.
        $this->customerService->createOrUpdate($order);

        if ($this->isCustomerUsingNewPaymentMethod($postedData)) {
            $newPaymentMethodId = $this->getNewPaymentMethodId($postedData);
            $paymentMethod = $this->paymentMethodService->getPaymentMethod($newPaymentMethodId);

            // Reuse and attach payment method is for logged-in customers only.
            if ($this->canReuseAndAttachPaymentMethod($order)) {
                $paymentMethod = $this->findMatchForPaymentMethodWithOrder($order, $paymentMethod) ?? $paymentMethod;

                // Attach new payment method to the customer if the customer opted in to save the payment method.
                if (
                    $paymentMethod->hasId($newPaymentMethodId) &&
                    $this->isCustomerOptedInToPaymentInformation($postedData, $order)
                ) {
                    $this->paymentMethodService->attachPaymentMethodToCustomer(
                        $paymentMethod->getId(),
                        $this->orderRepository->getCustomerId($order)
                    );

                    // Set customer wants to save card.
                    // This helps to save the customer payment method on the Stripe with appropriate,
                    // future use capabilities.
                    add_filter(
                        'wc_stellarpay_stripe_generate_payment_intent_data',
                        [ $this, 'setPaymentMethodToSaveForFutureUse'],
                        999
                    );
                }
            }

            // At this point, we support saving card type the Stripe payment method to the customer on the website.
            // Save payment information is supported only for logged-in customers.
            if ($this->canSavePaymentMethod($paymentMethod, $order, $postedData)) {
                $this->saveCardTypePaymentMethod($paymentMethod, $order->get_customer_id('edit'));
            }
        } elseif ($this->isCustomerOptedInToUseSavedPaymentMethod($postedData)) {
            $savedPaymentMethod = $this->getSavedPaymentMethod($order, $postedData);
            $paymentMethod = $this->paymentMethodService->getPaymentMethod($savedPaymentMethod->get_token());
        } else {
            throw new Exception('Invalid payment method.');
        }

        // Attach the payment method to the order.
        $this->orderRepository->setPaymentMethodId($order, $paymentMethod->getId());
    }

    /**
     * Note
     *
     * This function is callback for "woocommerce_checkout_order_processed" action hook,
     * This class generates an invokable object, and it is advisable to use them.
     *
     * @since 1.0.1
     */
    public function handleOrderProcessing(int $orderId): void
    {
        $paymentContext = new PaymentContext();
        $paymentContext->set_order(wc_get_order($orderId));

        $this->__invoke($paymentContext);
    }

    /**
     * This function returns the saved customer payment method from the saved payment method token id.
     *
     * @since 1.0.0
     * @throws Exception
     */
    protected function getSavedPaymentMethod(WC_Order $order, array $paymentData): WC_Payment_Token
    {
        $tokenId = $this->getPaymentMethodTokenId($paymentData);
        $token = WC_Payment_Tokens::get($tokenId);

        if (! $token instanceof WC_Payment_Token) {
            throw new Exception('Invalid saved payment method token.');
        }

        if ($token->get_user_id() !== $order->get_customer_id()) {
            throw new Exception('Invalid payment method selected.');
        }

        return $token;
    }

    /**
     * @since 1.0.0
     */
    protected function getNewPaymentMethodId(array $postedData): ?string
    {
        return $postedData['wc-' . Constants::GATEWAY_ID . '-payment-method-id'] ?? null;
    }

    /**
     * @since 1.0.0
     */
    protected function getPaymentMethodTokenId(array $postedData): ?int
    {
        $id = Constants::GATEWAY_ID;
        $paramName = "wc-$id-payment-token";

        $tokenId = $postedData[$paramName] ?? null;

        if (! is_numeric($tokenId)) {
            return null;
        }

        return absint($tokenId);
    }


    /**
     * Check if the customer is using a new payment method.
     *
     * @since 1.0.0
     */
    protected function isCustomerUsingNewPaymentMethod(array $paymentData): bool
    {
        return ! empty($this->getNewPaymentMethodId($paymentData));
    }

    /**
     * Check if the customer opted in to save the payment information.
     *
     * @since 1.7.0 Add support for the WooCommerce Subscriptions.
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    protected function isCustomerOptedInToPaymentInformation(array $paymentData, WC_Order $order): bool
    {
        $id = Constants::GATEWAY_ID;
        $paramName = "wc-{$id}-new-payment-method";

        // Return true,
        // - If the customer opted in to save the payment method or,
        // - If the order contains a subscription because Saved payment method is required for subscriptions.
        // - If the order contains a WooCommerce subscription because the saved payment method is required for WooCommerce subscriptions.
        return (isset($paymentData[$paramName]) && 'true' === $paymentData[$paramName] )
            || $this->hasOneOfSubscriptionTypeInTheOrder($order);
    }

    /**
     * Check if the customer opted in to use the saved payment method.
     *
     * @since 1.0.0
     */
    protected function isCustomerOptedInToUseSavedPaymentMethod(array $paymentData): bool
    {
        return null !== $this->getPaymentMethodTokenId($paymentData);
    }
}
