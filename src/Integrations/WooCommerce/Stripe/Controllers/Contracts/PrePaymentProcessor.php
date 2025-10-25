<?php

/**
 * This class is responsible to execute processes which are required before the WooCommerce order processing.
 *
 * This executes when customer process order with block checkout.
 *
 * A few processes which this class executes -
 * - Create Stripe customer, if not any
 * - Create Stripe payment method, if not any
 * - Attach payment method to the Stripe customer, if opt-in
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers\Contracts;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Services\CustomerService;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\FindMatchForPaymentMethod;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentToken;
use StellarPay\Integrations\WooCommerce\Traits\MixedSubscriptionUtilities;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use WC_Order;
use WC_Payment_Tokens;

/**
 * @since 1.7.0 Use the trait `MixedSubscriptionUtilities`.
 * @since 1.0.0
 */
abstract class PrePaymentProcessor
{
    use MixedSubscriptionUtilities;
    use WooCommercePaymentToken;
    use FindMatchForPaymentMethod;

    /**
     * @since 1.0.0
     */
    protected CustomerService $customerService;

    /**
     * @since 1.0.0
     */
    protected SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    protected PaymentMethodService $paymentMethodService;

    /**
     * @since 1.0.0
     */
    protected Request $request;

    /**
     * Constructor.
     * @since 1.0.0
     */
    public function __construct(
        OrderRepository $orderRepository,
        SettingRepository $settingRepository,
        PaymentMethodService $paymentMethodService,
        CustomerService $customerService,
        Request $request
    ) {
        $this->orderRepository = $orderRepository;
        $this->settingRepository = $settingRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->customerService = $customerService;
        $this->request = $request;
    }

    /**
     * Check if the customer is using a new payment method.
     *
     * @since 1.0.0
     */
    abstract protected function isCustomerUsingNewPaymentMethod(array $paymentData): bool;

    /**
     * Check if the customer opted in to save the payment information.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    abstract protected function isCustomerOptedInToPaymentInformation(array $paymentData, WC_Order $order): bool;

    /**
     * Set the payment method to save for future use.
     *
     * This function is used to set the payment method to save for future use on the Stripe.
     * This function is used with the filter `wc_stellarpay_stripe_generate_payment_intent_data`.
     *
     * @since 1.0.0
     */
    public function setPaymentMethodToSaveForFutureUse(array $data): array
    {
        $data['setup_future_usage'] = PaymentIntentDTO::SETUP_FUTURE_USAGE_OFF_SESSION;

        return $data;
    }

    /**
     * Check if the payment method can be saved.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function canSavePaymentMethod(PaymentMethodDTO $paymentMethod, WC_Order $order, array $postedData): bool
    {
        if (! ($customerId = $order->get_customer_id('edit'))) {
            return false;
        }

        if (! $this->isCustomerOptedInToPaymentInformation($postedData, $order) || ! $paymentMethod->isCard()) {
            return false;
        }

        // If the payment method is already saved, then return false.
        $tokens = WC_Payment_Tokens::get_customer_tokens($customerId, Constants::GATEWAY_ID);
        if (! empty($tokens)) {
            foreach ($tokens as $token) {
                if ($token->get_token() === $paymentMethod->getId()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if the attached payment method can be reused.
     *
     * @since 1.0.0
     */
    protected function canReuseAndAttachPaymentMethod(WC_Order $order): bool
    {
        // Customer should be logged in.
        // Order must have a customer ID if customer is logged in.
        return (bool) absint($order->get_customer_id('edit'));
    }
}
