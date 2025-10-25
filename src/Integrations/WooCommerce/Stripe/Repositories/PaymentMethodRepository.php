<?php

/**
 * This class is used to handle payment token related queries.
 *
 * @package StellarPay\Integrations\WooCommerce\Models
 * @since 1.1.0
 *
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\QueryBuilder;
use WC_Order;
use WC_Payment_Token_CC;

/**
 * @since 1.1.0
 */
class PaymentMethodRepository
{
    protected OrderRepository $orderRepository;
    protected PaymentMethodService $paymentMethodService;
    protected SettingRepository $settingRepository;

    /**
     * @since 1.1.0
     */
    public function __construct(
        OrderRepository $orderRepository,
        PaymentMethodService $paymentMethodService,
        SettingRepository $settingRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->settingRepository = $settingRepository;
    }

    /**
     * @since 1.1.0
     */
    public function findByStripePaymentMethodId(string $stripePaymentMethodId): ?WC_Payment_Token_CC
    {
        $query = $this->prepareQuery()
            ->select('token_id')
            ->where('token', $stripePaymentMethodId);
        $result = $query->get(ARRAY_A);

        if (! $result) {
            return null;
        }

        $paymentTokenId = $result['token_id'];

        return new WC_Payment_Token_CC($paymentTokenId);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public function getPaymentMethodTitleForReceipt(string $stripePaymentMethodId, WC_Order $order = null): string
    {
        $paymentMethod = $this->findByStripePaymentMethodId($stripePaymentMethodId);

        if ($paymentMethod) {
            return sprintf(
            // translators: %1$s - card type; %1$s - card last 4 digits; %1$s - card expiry month; %1$s - card expiry year;
                esc_html__('%1$s ending in %2$s (expires %3$s/%4$s)', 'stellarpay'),
                ucfirst($paymentMethod->get_card_type()),
                $paymentMethod->get_last4(),
                $paymentMethod->get_expiry_month(),
                $paymentMethod->get_expiry_year()
            );
        }

        if (! $order instanceof WC_Order) {
            throw new InvalidArgumentException('Order is not provided');
        }

        // Bootstrap stripe client with the right payment gateway mode.
        $orderPaymentGatewayMode = $this->orderRepository->getPaymentGatewayMode($order);
        if ($orderPaymentGatewayMode->notMatch($this->settingRepository->getPaymentGatewayMode())) {
            $stripeClient = Client::getClient($orderPaymentGatewayMode);
            $this->paymentMethodService->setHttpClient($stripeClient);
        }

        $paymentMethodId = $this->orderRepository->getPaymentMethodId($order);
        $paymentMethod = $this->paymentMethodService->getPaymentMethod($paymentMethodId);

        // Display card payment method.
        if ($paymentMethod->isCard()) {
            return sprintf(
                '%1$s ending in %2$s (expires %3$s/%4$s)',
                ucfirst($paymentMethod->getCardBrand()),
                $paymentMethod->getCardLast4(),
                $paymentMethod->getCardExpMonth(),
                $paymentMethod->getCardExpYear()
            );
        }

        return strtoupper(str_replace('_', ' ', $paymentMethod->getType()));
    }

    /**
     * @since 1.1.0
     */
    public function prepareQuery(): QueryBuilder
    {
        return DB::table('woocommerce_payment_tokens');
    }
}
