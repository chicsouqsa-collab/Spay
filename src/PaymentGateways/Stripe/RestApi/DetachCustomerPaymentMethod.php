<?php

/**
 * This class is responsible for detaching a payment method from a customer.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\RestApi;

use Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use StellarPay\RestApi\Endpoints\ApiRoute;
use WC_Payment_Tokens;
use WP_Error;
use WP_REST_Request;

/**
 * Class DetachCustomerPaymentMethod
 *
 * @todo: check whether or not stripe detach payment method which attached to a subscription.
 *
 * @since 1.0.0
 */
class DetachCustomerPaymentMethod extends ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'detach-stripe-customer-payment-method';

    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    private PaymentMethodService $paymentMethodService;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository, PaymentMethodService $paymentMethodService)
    {
        parent::__construct();

        $this->orderRepository = $orderRepository;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * @since 1.0.0
     */
    public function register(): void
    {
        register_rest_route(
            $this->getNamespace(),
            $this->getEndpoint(),
            [
                'methods' => 'POST',
                'callback' => [$this, 'detachCustomerPaymentMethod'],
                'permission_callback' => [$this, 'permissionCheck'],
                'args' => [
                    'payment-method-id' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => esc_html__('The Stripe payment method id.', 'stellarpay'),
                        'validate_callback' => [$this, 'isValidPaymentMethodId']
                    ],
                    'payment-intent-id' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => esc_html__('The Stripe payment intent id.', 'stellarpay'),
                        'validate_callback' => [$this, 'isValidPaymentIntentId']
                    ],
                    'order-id' => [
                        'required' => true,
                        'type' => 'integer',
                        'description' => esc_html__('The WooCommerce order id.', 'stellarpay')
                    ],
                ],
            ]
        );
    }

    /**
     * @since 1.0.0
     *
     * @return bool|WP_Error
     */
    public function permissionCheck(WP_REST_Request $request)
    {
        $check = parent::permissionCheck($request);
        if (is_wp_error($check)) {
            return $check;
        }

        // Check if the order exists.
        $orderId = $request->get_param('order-id');
        $order = wc_get_order($orderId);
        if (! $order) {
            return new WP_Error(
                'stellarpay_order_not_found',
                esc_html__('Order not found.', 'stellarpay'),
                ['status' => 404]
            );
        }

        // Check if the payment method is ours.
        if (Constants::GATEWAY_ID !== $order->get_payment_method()) {
            return new WP_Error(
                'stellarpay_payment_method_not_stripe',
                esc_html__('Payment method is invalid', 'stellarpay'),
                ['status' => 400]
            );
        }

        // Check if the order is pending.
        if ('pending' !== $order->get_status('edit')) {
            return new WP_Error(
                'stellarpay_order_not_pending',
                esc_html__('Order is not pending.', 'stellarpay'),
                ['status' => 400]
            );
        }

        // Check if the order has a customer.
        if (! $order->get_customer_id('edit')) {
            return new WP_Error(
                'stellarpay_order_has_customer',
                esc_html__('Order does not have a customer.', 'stellarpay'),
                ['status' => 400]
            );
        }

        // @todo we should allow detaching payment method within ~30 minutes of order creation.

        // Check if the payment method id matches the order's payment method id.
        $paymentMethodId = $request->get_param('payment-method-id');
        $orderPaymentMethodId = $this->orderRepository->getPaymentMethodId($order);
        if ($paymentMethodId !== $orderPaymentMethodId) {
            return new WP_Error(
                'stellarpay_payment_method_id_mismatch',
                esc_html__('Payment method id mismatch.', 'stellarpay'),
                ['status' => 400]
            );
        }

        // Check if the payment intent id matches the order's payment intent id.
        $paymentIntentId = $request->get_param('payment-intent-id');
        $orderPaymentIntentId = $order->get_transaction_id('edit');
        if ($paymentIntentId !== $orderPaymentIntentId) {
            return new WP_Error(
                'stellarpay_payment_intent_id_mismatch',
                esc_html__('Payment intent id mismatch.', 'stellarpay'),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Detach a payment method from a customer.
     *
     * @since 1.0.0
     */
    public function detachCustomerPaymentMethod(WP_REST_Request $request)
    {
        try {
            $paymentMethodId = $request->get_param('payment-method-id');
            $orderId = $request->get_param('order-id');
            $order = wc_get_order($orderId);
            $customerId  = $order->get_customer_id('edit');

            $this->paymentMethodService->detachPaymentMethod($paymentMethodId);

            // Delete the payment method from the customer on the website.
            // We are only deleting newly added payment method from the customer.
            // But to prevent wrong deletion, we are doing a soft deleting.
            $savedPaymentTokens = WC_Payment_Tokens::get_customer_tokens($customerId, Constants::GATEWAY_ID);

            if ($savedPaymentTokens) {
                foreach ($savedPaymentTokens as $token) {
                    if ($token->get_token() === $paymentMethodId) {
                        $token->delete();
                    }
                }
            }
        } catch (Exception $e) {
            return new WP_Error(
                'stellarpay_detach_payment_method_failed',
                sprintf(
                   /* translators: 1: Error message */
                    esc_html__('Failed to detach payment method. Error: %1$s', 'stellarpay'),
                    $e->getMessage()
                ),
                ['status' => 400]
            );
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * @since 1.0.0
     */
    public function isValidPaymentIntentId($id): bool
    {
        return preg_match('/^pi_[A-Za-z0-9]+$/', $id) === 1;
    }

    /**
     * @since 1.0.0
     */
    public function isValidPaymentMethodId($id): bool
    {
        return preg_match('/^pm_[A-Za-z0-9]+$/', $id) === 1;
    }
}
