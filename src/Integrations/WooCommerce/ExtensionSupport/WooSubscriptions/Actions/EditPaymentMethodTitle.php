<?php

/**
 * This class handles payment method title rendering for subscription on various pages.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Actions;

use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;

/**
 * @since 1.7.0
 */
class EditPaymentMethodTitle
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @since 1.7.0
     */
    protected PaymentMethodRepository $paymentMethodTokenRepository;

    /**
     * @since 1.7.0
     */
    private OrderRepository $orderRepository;


    /**
     * @since 1.7.0
     */
    public function __construct(OrderRepository $orderRepository, PaymentMethodRepository $paymentMethodTokenRepository)
    {
        $this->orderRepository      = $orderRepository;
        $this->paymentMethodTokenRepository = $paymentMethodTokenRepository;
    }

    /**
     * @since 1.7.0
     */
    public function __invoke(string $paymentMethodTitle, \WC_Subscription $subscription, string $context): string
    {
        if ($this->matchPaymentGatewayInOrder($subscription)) {
            try {
                $paymentMethodId = $this->orderRepository->getPaymentMethodId($subscription);

                return $this->paymentMethodTokenRepository->getPaymentMethodTitleForReceipt($paymentMethodId, $subscription);
            } catch (\Exception $exception) {
                return $paymentMethodTitle;
            }
        }

        return $paymentMethodTitle;
    }
}
