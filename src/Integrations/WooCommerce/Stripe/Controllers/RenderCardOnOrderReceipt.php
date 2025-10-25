<?php

/**
 *  This class handles payment method title rendering on the WooCommerce receipt page.
 *
 *  By default, the WooCommerce prints the payment gateway title on the receipt page.
 *  This class renders a masked credit card number with additional information if Stripe payment method id card type,
 *  otherwise formatted Stripe payment method type.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use WC_Order;

/**
 * @since 1.7.0 Remove different hookable functions.
 * @since 1.0.0
 */
class RenderCardOnOrderReceipt
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.1.0
     */
    protected PaymentMethodRepository $paymentMethodTokenRepository;

    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;


    /**
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository, PaymentMethodRepository $paymentMethodTokenRepository)
    {
        $this->orderRepository      = $orderRepository;
        $this->paymentMethodTokenRepository = $paymentMethodTokenRepository;
    }

    /**
     * @since 1.1.0 Use PaymentMethodTokenRepository
     * @since 1.0.0
     */
    public function __invoke(string $paymentGatewayTitle, WC_Order $order): string
    {
        if ($this->canNotEditPaymentMethodTitle($order)) {
            return $paymentGatewayTitle;
        }

        try {
            $paymentMethodId = $this->orderRepository->getPaymentMethodId($order);

            return $this->paymentMethodTokenRepository->getPaymentMethodTitleForReceipt($paymentMethodId, $order);
        } catch (Exception $exception) {
            if (doing_filter('woocommerce_admin_order_data_after_payment_info')) {
                return  sprintf(
                    /* translators: 1: Stripe payment method id */
                    esc_html__('%s (Stripe Payment Method ID)', 'stellarpay'),
                    $this->orderRepository->getPaymentMethodId($order)
                );
            }

            return $paymentGatewayTitle;
        }
    }

    /**
     * @since 1.0.0
     */
    private function canNotEditPaymentMethodTitle(WC_Order $order): bool
    {
        return ! $this->matchPaymentGatewayInOrder($order) || ! $order->get_transaction_id('edit');
    }
}
