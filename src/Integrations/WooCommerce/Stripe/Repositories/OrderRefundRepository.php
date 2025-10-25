<?php

/**
 * Order Refund Repository.
 *
 * This class is used to manage the order refund data for Woocommerce and Stripe integration.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Repositories;

use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use WC_Order_Refund;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Class OrderRefundRepository
 *
 * @since 1.0.0
 */
class OrderRefundRepository
{
    /**
     * @since 1.0.0
     */
    private string $refundIdKey;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->refundIdKey = dbMetaKeyGenerator('stripe_refund_id', true);
    }

    /**
     * This method set the refund id for a given WooCommerce order refund.
     *
     * Use save method to save the refund id to the order meta.
     *
     * @since 1.0.0
     */
    public function setRefundId(WC_Order_Refund $orderRefund, $refundId): bool
    {
        return (bool) $orderRefund->update_meta_data($this->refundIdKey, $refundId);
    }

    /**
     * This method returns the refund id for a given WooCommerce order refund.
     *
     * @since 1.0.0
     */
    public function getRefundId(WC_Order_Refund $orderRefund): string
    {
        return (string) $orderRefund->get_meta($this->refundIdKey);
    }

    /**
     * Returns the WooCommerce order refund by the given Stripe refund id.
     *
     * @since 1.1.0
     */
    public function findByStripeRefundId(string $refundId): ?WC_Order_Refund
    {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $refundPosts = get_posts([
            'post_type'              => 'shop_order_refund',
            'meta_key'               => $this->refundIdKey,
            'meta_value'             => $refundId,
            'post_status'            => 'any',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_value

        if (empty($refundPosts)) {
            return null;
        }

        $refundPost = current($refundPosts);

        return new WC_Order_Refund($refundPost->ID);
    }

     /**
     * @since 1.1.0
     */
    public function getRefundAmount(WC_Order_Refund $orderRefund): Money
    {
        $refundAmount = abs(floatval($orderRefund->get_total()));

        return Money::make($refundAmount, $orderRefund->get_currency());
    }
}
