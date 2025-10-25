<?php

/**
 * Customer Repository.
 *
 * This class is used to retrieve customer data.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe/Strategies
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Traits\WooCommerceTablesTrait;
use WC_Payment_Tokens;
use WP_User;

use function StellarPay\Core\dbMetaKeyGenerator;
use function wc_get_orders;

/**
 * Class CustomerRepository
 *
 * @since 1.0.0
 */
class CustomerRepository
{
    use WooCommerceTablesTrait;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    private string $customerIdKeyPrefix;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->customerIdKeyPrefix = dbMetaKeyGenerator('stripe_customer_id', true);
    }

    /**
     * @since 1.0.0
     */
    public function getCustomerIdKey(PaymentGatewayMode $paymentGatewayMode): string
    {
        $modeId = $paymentGatewayMode->getId();

        return $this->customerIdKeyPrefix . "_$modeId";
    }

    /**
     * This function gets the Stripe customer id from the user metadata.
     *
     * @since 1.0.0
     */
    public function getCustomerIdByUser(WP_User $user, PaymentGatewayMode $paymentGatewayMode): string
    {
        return get_user_meta($user->ID, $this->getCustomerIdKey($paymentGatewayMode), true);
    }

    /**
     * This function saves the Stripe customer id to the user metadata.
     *
     * @since 1.0.0
     */
    public function setCustomerIdByUser(WP_User $user, string $customerId, PaymentGatewayMode $paymentGatewayMode): bool
    {
        return (bool) update_user_meta($user->ID, $this->getCustomerIdKey($paymentGatewayMode), $customerId);
    }

    /**
     * This function should save the customer id.
     *
     * It will save the Stripe customer id to persistence cache to prevent multiple calls to the database.
     *
     * @since 1.0.0
     */
    public function getCustomerIdByGuestEmail(string $email, PaymentGatewayMode $paymentGatewayMode): ?string
    {
        // The "wc_get_orders" function does not support the custom meta-queries.
        // This filter allows setting up the meta-query.
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', [ __CLASS__, 'wcOrderFilterByCustomerIdKey'], 10, 2);

        $orders = wc_get_orders([
            'limit' => 1,
            'billing_email' => $email,
            'meta_key' => OrderRepository::getCustomerIdKeyPrefix() . '_' . $paymentGatewayMode->getId(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'meta_compare' => 'EXISTS'
        ]);

        remove_filter('woocommerce_order_data_store_cpt_get_orders_query', [ __CLASS__, 'wcOrderFilterByCustomerIdKey']);

        return $orders ? $this->orderRepository->getCustomerId(current($orders)) : null;
    }

    /**
     * @since 1.0.0
     */
    public static function wcOrderFilterByCustomerIdKey(array $wpQueryArgs, array $queryVars): array
    {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $wpQueryArgs['meta_query'] = array_merge(
            $wpQueryArgs['meta_query'],
            [
                'relation' => 'AND',
                [
                    'key' => $queryVars['meta_key'],
                    'compare' => $queryVars['meta_compare'],
                ]
            ]
        );
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

        return $wpQueryArgs;
    }

    /**
     * This function returns the default payment method.
     *
     * @since 1.0.0
     */
    public function getDefaultPaymentMethod(WP_User $user): ?string
    {
        $paymentMethod = WC_Payment_Tokens::get_customer_default_token($user->ID);

        if (! $paymentMethod) {
            return null;
        }

        return $paymentMethod->get_token();
    }
}
