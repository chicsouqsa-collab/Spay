<?php

/**
 * Customer Data Strategy.
 *
 * This class is responsible to provide customer data to data transfer object for Stripe customer.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Strategies
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use StellarPay\Core\Exceptions\Primitives\RuntimeException;
use WC_Order;

/**
 * Class CustomerDataStrategy
 *
 * @since 1.0.0
 */
class CustomerDataStrategy implements DataStrategy
{
    /**
     * @since 1.0.0
     */
    private ?WC_Order $order;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(WC_Order $order = null)
    {
        $this->order = $order;
    }

    /**
     * Set the WooCommerce order.
     *
     * @since 1.0.0
     */
    public function setOrder(WC_Order $order): void
    {
        $this->order = $order;
    }

    /**
     * This function generates data for the Stripe customer rest api query.
     *
     * @since 1.0.0
     * @throws RuntimeException
     */
    public function generateData(): array
    {
        if (! $this->order) {
            throw new RuntimeException('The WooCommerce Order is required to generate customer data.');
        }

        $name = $this->getName();
        $email = $this->order->get_billing_email();

        $data = [
            'email' => $email,
            'name' => $name,
            'description' => $this->getDescription($email, $name),
            'address' => $this->getAddress(),
        ];

        // Add shipping details if available.
        if (method_exists($this->order, 'get_shipping_postcode') && !empty($this->order->get_shipping_postcode())) {
            $data['shipping'] = $this->getShippingAddress();
        }

        $data['metadata'] = [
            'site_url' => esc_url(get_site_url()),
        ];

        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the customer data.
         *
         * @since 1.0.0
         *
         * @param array $data
         * @param WC_Order $order
         */
        return apply_filters(
            'wc_stellarpay_stripe_generate_customer_data',
            $data,
            $this->order
        );
    }

    /**
     * Get the name for the customer.
     *
     * @since 1.0.0
     */
    private function getName(): string
    {
        $firstName = $this->order->get_billing_first_name();
        $lastName = $this->order->get_billing_last_name();

        return trim($firstName . ' ' . $lastName);
    }

    /**
     * Get the description for the customer.
     *
     * @since 1.0.0
     */
    private function getDescription(string $email, string $name): string
    {
        $description = sprintf(
            // translators: 1: Name.
            esc_html__('Name: %1$s, Guest', 'stellarpay'),
            $name
        );

        $user = $this->order->get_user() ?: get_user_by('email', $email);

        if ($user) {
            $description = sprintf(
                // translators: 1: Name, 2 Username.
                esc_html__('Name: %1$s, Username: %2$s', 'stellarpay'),
                $name,
                $user->user_login
            );
        }

        return  $description;
    }

    /**
     * Get the address for the customer.
     *
     * @since 1.0.0
     */
    private function getAddress(): array
    {
        return [
            'line1'       => $this->order->get_billing_address_1(),
            'line2'       => $this->order->get_billing_address_2(),
            'postal_code' => $this->order->get_billing_postcode(),
            'city'        => $this->order->get_billing_city(),
            'state'       => $this->order->get_billing_state(),
            'country'     => $this->order->get_billing_country(),
        ];
    }

    /**
     * Get the shipping address for the customer.
     *
     * @since 1.0.0
     */
    private function getShippingAddress(): array
    {
        return [
            'name'    => $this->getName(),
            'address' => [
                'line1'       => $this->order->get_shipping_address_1(),
                'line2'       => $this->order->get_shipping_address_2(),
                'postal_code' => $this->order->get_shipping_postcode(),
                'city'        => $this->order->get_shipping_city(),
                'state'       => $this->order->get_shipping_state(),
                'country'     => $this->order->get_shipping_country(),
            ],
        ];
    }
}
