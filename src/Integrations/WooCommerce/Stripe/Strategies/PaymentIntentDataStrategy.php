<?php

/**
 * PaymentIntentStrategy.
 *
 * This class is used to generate data for the payment intent.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Integrations\WooCommerce\Traits\MixedSubscriptionUtilities;
use StellarPay\Integrations\WooCommerce\ValueObjects\OrderType;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use WC_Order;

/**
 * Class PaymentIntentDataStrategy
 *
 * @since 1.8.0 Flag payment type as subscription when the Woocommerce or StellaPay subscription is in cart.
 * @since 1.7.0 Add support to set amount and an offsite payment type.
 * @since 1.0.0
 */
class PaymentIntentDataStrategy implements DataStrategy
{
    use MixedSubscriptionUtilities;

    /**
     * @since 1.7.0
     */
    protected bool $offSessionPayment = false;

    /**
     * @since 1.0.0
     */
    private WC_Order $order;

    /**
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository, SettingRepository $settingRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->settingRepository = $settingRepository;
    }

    /**
     * Set the order.
     *
     * @since 1.7.0 Return class instance.
     * @since 1.0.0
     */
    public function setOrder(WC_Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function offSessionPayment(): self
    {
        $this->offSessionPayment = true;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @since 1.1.0 Use enum instead of string for an order type
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function generateData(): array
    {
        $orderAmount = Money::make(
            (float)$this->order->get_total(), // Woocommerce returns the total amount as a string.
            $this->order->get_currency()
        );
        $billingEmail = $this->order->get_billing_email();
        $billingFirstName = $this->order->get_billing_first_name();
        $billingLastName = $this->order->get_billing_last_name();

        $data = [];

        // Add order details.
        $data['customer'] = $this->orderRepository->getCustomerId($this->order);
        $data['payment_method'] = $this->orderRepository->getPaymentMethodId($this->order);
        $data['currency'] = strtolower($orderAmount->getCurrencyCode());
        $data['amount'] = $orderAmount->getMinorAmount();
        $data['description'] = sprintf(
            /* translators: 1: Blog name 2: Order number */
            esc_html__('%1$s - Order %2$s', 'stellarpay'),
            wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            $this->order->get_order_number()
        );

        // Add a receipt email if available. It will trigger a receipt to be sent to the customer from the Stripe.
        if (!empty($billingEmail) && $this->settingRepository->canSendStripeReceipts()) {
            $data['receipt_email'] = $billingEmail;
        }

        // Add shipping details if available.
        if (method_exists($this->order, 'get_shipping_postcode') && !empty($this->order->get_shipping_postcode())) {
            $data['shipping'] = [
                'name' => trim($this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name()),
                'phone' => $this->order->get_shipping_phone(),
                'address' => [
                    'line1' => $this->order->get_shipping_address_1(),
                    'line2' => $this->order->get_shipping_address_2(),
                    'city' => $this->order->get_shipping_city(),
                    'country' => $this->order->get_shipping_country(),
                    'postal_code' => $this->order->get_shipping_postcode(),
                    'state' => $this->order->get_shipping_state(),
                ],
            ];
        }

        // Add metadata.
        $billingName = trim(sanitize_text_field($billingFirstName) . ' ' . sanitize_text_field($billingLastName));
        $data['metadata'] = [
            'payment_type' => $this->hasOneOfSubscriptionTypeInTheOrder($this->order)
                ? OrderType::SUBSCRIPTION
                : OrderType::ONETIME,
            'order_key' => $this->order->get_order_key(),
            'customer_name' => $billingName,
            'customer_email' => sanitize_email($billingEmail),
            'order_id' => $this->order->get_order_number(),
            'site_url' => esc_url(get_site_url()),
        ];

        // Add payment statement descriptor.
        if (
            $this->settingRepository->isPaymentStatementDescriptorEnabled()
            && ( $statementDescriptor = $this->settingRepository->getPaymentStatementDescriptor() )
        ) {
            $data['statement_descriptor'] = $statementDescriptor;
            $data['statement_descriptor_suffix'] = $statementDescriptor;
        }

        // Add off session payment.
        if ($this->offSessionPayment) {
            $data['off_session'] = true;
            $data['confirm'] = true;
        }

        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the payment intent data.
         *
         * @since 1.0.0
         *
         * @param array $data
         * @param WC_Order $order
         */
        return apply_filters(
            'wc_stellarpay_stripe_generate_payment_intent_data',
            $data,
            $this->order
        );
    }
}
