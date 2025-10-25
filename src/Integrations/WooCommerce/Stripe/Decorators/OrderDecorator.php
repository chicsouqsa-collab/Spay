<?php

/**
 * This class provides additional function to execute logics on WC_Order
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Decorators
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Decorators;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderStatus;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WP_User;

/**
 * @since 1.0.0
 */
class OrderDecorator
{
    /**
     * @since 1.0.0
     */
    protected WC_Order $wcOrder;

    /**
     * @since 1.0.0
     */
    public function __construct(WC_Order $order)
    {
        $this->wcOrder = $order;
    }

    /**
     * Check if the order is a match for the payment method.
     *
     * @since 1.0.0
     */
    public function isMatchPaymentMethod(): bool
    {
        $paymentGatewayId = $this->wcOrder->get_payment_method();

        return Constants::GATEWAY_ID === $paymentGatewayId;
    }

    /**
     * This function returns whether the order is for a registered user.
     *
     * @since 1.0.0
     */
    public function isRegisteredUser(): bool
    {
        $user = $this->getRegisterUser();

        return $user instanceof WP_User;
    }


    /**
     * This function returns the user object for the order.
     *
     * @since 1.0.0
     */
    public function getRegisterUser(): ?WP_User
    {
        $user = $this->wcOrder->get_user();
        if ($user) {
            return $user;
        }

        $user = get_user_by('email', $this->wcOrder->get_billing_email());
        if ($user) {
            return $user;
        }

        return null;
    }

    /**
     * @since 1.8.0
     */
    public function isPaymentPending(): bool
    {
        return $this->wcOrder->get_status() === OrderStatus::PENDING;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function deleteAllSubscriptions(): void
    {
        $subscriptions = Subscription::findAllByFirstOrderId($this->wcOrder->get_id());

        if (! $subscriptions) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }
    }
}
