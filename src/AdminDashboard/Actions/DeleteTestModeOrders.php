<?php

/**
 * This class is responsible for removing orders in test mode.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use StellarPay\AdminDashboard\Actions\Contracts\TestDataDeletionRule;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;

/**
 * @since 1.2.0
 */
class DeleteTestModeOrders implements TestDataDeletionRule
{
    /**
     * @since 1.2.0
     */
    public function __invoke(): void
    {
        while (true) {
            $orders = wc_get_orders(
                [
                    'limit' => 10,
                    'meta_key' => OrderRepository::getPaymentModeKey(),
                    'meta_value' => PaymentGatewayMode::TEST, // phpcs:ignore WordPress.DB.SlowDBQuery
                ]
            );

            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $order->delete(true);
            }
        }
    }
}
