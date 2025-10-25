<?php

/**
 * Order Repository.
 *
 * This class is used to manage the order data for Woocommerce and Stripe integration.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use WC_Order;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Class Order
 * @since 1.0.0
 */
class OrderRepository
{
    /**
     * @since 1.0.0
     */
    protected string $paymentIntentFeeKey;

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->paymentIntentFeeKey = dbMetaKeyGenerator('stripe_payment_intent_fee', true);
    }

    /**
     * @since 1.0.0
     */
    public static function getCustomerIdKeyPrefix(): string
    {
        return dbMetaKeyGenerator('stripe_customer_id', true);
    }

    /**
     * @since 1.0.0
     */
    public function getCustomerIdKey(WC_Order $order): string
    {
        $modeId = $this->getPaymentGatewayMode($order)->getId();

        return self::getCustomerIdKeyPrefix() . "_$modeId";
    }

    /**
     * @since 1.0.0
     */
    public static function getPaymentModeKey(): string
    {
        return dbMetaKeyGenerator('stripe_payment_mode', true);
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentMethodIdKey(): string
    {
        return dbMetaKeyGenerator('stripe_payment_method_id', true);
    }

    /**
     * This method saves the Stripe customer id to WooCommerce order meta.
     *
     * Note - Use WC_Order::save() function to persist changes in your order.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function setCustomerId(WC_Order $order, $customerId): bool
    {
        return (bool) $order->update_meta_data($this->getCustomerIdKey($order), $customerId);
    }

    /**
     * This method returns the Stripe customer id for a given WooCommerce order.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function getCustomerId(WC_Order $order): string
    {
        return $order->get_meta($this->getCustomerIdKey($order));
    }

    /**
     * This method saves the Stripe payment method id to WooCommerce order metadata.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function setPaymentMethodId(WC_Order $order, string $paymentMethodId): bool
    {
        $order->update_meta_data($this->getPaymentMethodIdKey(), $paymentMethodId);

        return (bool) absint($order->save());
    }

    /**
     * This method returns the Stripe payment method id for a given WooCommerce order metadata.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function getPaymentMethodId(WC_Order $order): string
    {
        return $order->get_meta($this->getPaymentMethodIdKey());
    }

    /**
     * This method returns the order that matches the payment intent id.
     *
     * @since 1.0.0
     */
    public function getOrderByPaymentIntentId(string $paymentIntentId): ?WC_Order
    {
        $orders = wc_get_orders([
            'transaction_id' => $paymentIntentId,
            'limit' => 1,
        ]);

        if (!empty($orders)) {
            return $orders[0];
        }

        return null;
    }

    /**
     * This method saves the test order flag to WooCommerce order meta.
     *
     * @since 1.0.0
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function isTestOrder(WC_Order $order): bool
    {
        return $order->get_meta(self::getPaymentModeKey()) === PaymentGatewayMode::TEST;
    }

    /**
     * @since 1.0.0
     */
    public function setPaymentGatewayMode(WC_Order $order, PaymentGatewayMode $paymentGatewayMode): bool
    {
        $order->update_meta_data(self::getPaymentModeKey(), $paymentGatewayMode->getId());

        return (bool) absint($order->save());
    }


    /**
     * @since 1.0.0
     */
    public function getPaymentGatewayMode(WC_Order $order): PaymentGatewayMode
    {
        return $this->isTestOrder($order) ? PaymentGatewayMode::test() : PaymentGatewayMode::live();
    }

    /**
     * @since 1.0.0
     */
    public function setPaymentIntentFee(WC_Order $order, \StellarPay\Core\ValueObjects\Money $amount): bool
    {
        $order->update_meta_data($this->paymentIntentFeeKey, (string) $amount->getAmount());
        return (bool) absint($order->save());
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentIntentFee(WC_Order $order): ?Money
    {
        $fee = $order->get_meta($this->paymentIntentFeeKey, true, 'edit');

        if (! $fee) {
            return null;
        }

        return Money::make((float) $fee, $order->get_currency());
    }
}
