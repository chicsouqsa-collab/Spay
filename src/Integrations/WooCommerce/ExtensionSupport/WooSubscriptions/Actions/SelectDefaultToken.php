<?php

/**
 * This action is used to select the default token on the subscription update payment method page.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions;

use StellarPay\Core\Facades\QueryVars;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\WooSubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use WC_Payment_Token;
use WC_Payment_Token_CC;

/**
 * @since 1.7.0
 */
class SelectDefaultToken
{
    use WooSubscriptionUtilities;
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.7.0
     */
    protected PaymentMethodRepository $paymentMethodRepository;

    /**
     * @since 1.7.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.7.0
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository, OrderRepository $orderRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @since 1.7.0
     */
    public function __invoke(bool $default, WC_Payment_Token $token): bool
    {
        if (!$this->isWooSubscriptionUpdatePaymentMethodPage()) {
            return $default;
        }

        $orderId = QueryVars::get('order-pay');

        $order = wc_get_order($orderId);

        if (! $this->matchPaymentGatewayInOrder($order)) {
            return $default;
        }

        $paymentMethodIdKey = $this->orderRepository->getPaymentMethodIdKey();
        $paymentMethodId = $order->get_meta($paymentMethodIdKey);

        if (empty($paymentMethodId)) {
            return $default;
        }

        $subscriptionToken = $this->paymentMethodRepository->findByStripePaymentMethodId($paymentMethodId);

        if ($subscriptionToken instanceof WC_Payment_Token_CC) {
            return $token->get_id() === $subscriptionToken->get_id();
        }

        return $default;
    }
}
