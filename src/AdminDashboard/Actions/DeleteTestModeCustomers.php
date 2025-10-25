<?php

/**
 * This class is responsible for removing customers in test mode i.e.
 * when customers have only orders in test mode.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use Exception;
use StellarPay\AdminDashboard\Actions\Contracts\TestDataDeletionRule;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\CustomerRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use WP_User_Query;

use function wp_delete_user;

/**
 * @since 1.2.0
 */
class DeleteTestModeCustomers implements TestDataDeletionRule
{
    /**
     * @since 1.2.0
     */
    protected CustomerRepository $customerRepository;

    /**
     * @since 1.2.0
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @since 1.2.0
     * @throws Exception
     */
    public function __invoke(): void
    {
        $testMode = PaymentGatewayMode::TEST();
        $offset = 0;

        while (true) {
            // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $customersQuery = new WP_User_Query(
                [
                    'fields' => 'ID',
                    'role' => 'customer',
                    'number' => 10,
                    'offset' => $offset,
                    'orderby' => 'ID',
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => $this->customerRepository->getCustomerIdKey($testMode),
                            'compare' => 'EXISTS',
                        ],
                        [
                            'key' => $this->customerRepository->getCustomerIdKey(PaymentGatewayMode::LIVE()),
                            'compare' => 'NOT EXISTS',
                        ]
                    ],
                ]
            );
            // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

            $customers = $customersQuery->get_results();
            if (empty($customers)) {
                break;
            }

            foreach ($customers as $customerId) {
                if ($this->hasOrderWithOtherPaymentGateway(absint($customerId))) {
                    $offset++;
                    continue;
                }

                wp_delete_user($customerId);
            }
        }
    }


    /**
     * @since 1.2.0
     * @throws Exception
     */
    public function hasOrderWithOtherPaymentGateway(int $customerId): bool
    {
        $orders = wc_get_orders([
            'customer_id' => $customerId,
            'meta_key' => OrderRepository::getPaymentModeKey(),
            'meta_compare' => 'NOT EXISTS',
            'limit' => 1,
        ]);

        return count($orders) > 0;
    }
}
