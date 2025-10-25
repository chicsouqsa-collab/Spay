<?php

/**
 * This class handles api request for "stellarpay/v1/performances" endpoint.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Analytics\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Analytics\RestApi;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use DateTime;
use stdClass;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Analytics\RestApi\DataStores\OrderStats;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.0.0
 */
class Performances extends StatsApiRoute
{
    protected string $endpoint = 'performances';

    /**
     * @since 1.0.0
     */
    protected DateTime $startDate;

    /**
     * @since 1.0.0
     */
    protected DateTime $endDate;

    /**
     * @since 1.0.0
     * @throws Exception|\Exception
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        $this->setupDatesByPeriod($request);

        $result = $this->getReportSalesData($this->startDate, $this->endDate);

        $result['growth'] = $this->getGrowthResult($result);

        return rest_ensure_response($result);
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    private function getFailedOrderCount(DateTime $startDate, DateTime $endDate): int
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
            SELECT
                COUNT(order_id) as count
            FROM
                {$wpdb->prefix}wc_order_stats
            WHERE
                date_created >= %s
                AND date_created <= %s
                AND status = 'wc-failed'
            ORDER BY
                date_created DESC
            ",
            [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s')
            ]
        );

        $cacheKey = $this->getCacheKey($sql);
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        $result = $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

        if (empty($result)) {
            return 0;
        }

        $result = absint($result);

        Cache::set($cacheKey, $result);

        return $result;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    private function getCustomerStats(DateTime $startDate, DateTime $endDate): object
    {
        global $wpdb;

        $excludedOrderStatuses = OrderStats::getExcludedReportOrderStatuses();

        $excludedOrderStatusesPlaceholders = implode(', ', array_fill(0, count($excludedOrderStatuses), '%s'));

        // phpcs:disable WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL
        $sql = $wpdb->prepare(
            "
            SELECT
                customer_id,
                returning_customer
            FROM
                {$wpdb->prefix}wc_order_stats
            WHERE
                date_created >= %s
                AND date_created <= %s
                AND status NOT IN ({$excludedOrderStatusesPlaceholders})
            ORDER BY
                date_created DESC
            ",
            array_merge(
                [
                    $startDate->format('Y-m-d H:i:s'),
                    $endDate->format('Y-m-d H:i:s'),
                ],
                $excludedOrderStatuses
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL

        $cacheKey = $this->getCacheKey($sql);
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return $cachedData;
        }

        $customers = $wpdb->get_results($sql); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

        $result = new stdClass();

        if (empty($customers)) {
            $result->returningCustomerCount = 0;
            $result->newCustomerCount = 0;

            return $result;
        }

        $returningCustomerIds = [];
        $newCustomerIds = [];

        array_map(function ($customer) use (&$returningCustomerIds, &$newCustomerIds) {
            if (1 === absint($customer->returning_customer)) {
                $returningCustomerIds[] = $customer->customer_id;
                return;
            }

            $newCustomerIds[] = $customer->customer_id;
        }, $customers);

        $returningCustomerIds = array_unique($returningCustomerIds);
        $newCustomerIds = array_unique($newCustomerIds);
        $newCustomerIds = array_diff($newCustomerIds, $returningCustomerIds);

        $result->returningCustomerCount = count($returningCustomerIds);
        $result->newCustomerCount = count($newCustomerIds);

        Cache::set($cacheKey, $result);

        return $result;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    private function getGrowthResult(array $result): array
    {
        $wooReportSalesData = $this->getReportSalesData($this->lastStartDate, $this->lastEndDate);

        return [
            'grossSales' => $this->getGrowth($wooReportSalesData['grossSales'], $result['grossSales']),
            'netSales' => $this->getGrowth($wooReportSalesData['netSales'], $result['netSales']),
            'orderCount' => $this->getGrowth($wooReportSalesData['orderCount'], $result['orderCount']),
            'totalRefunds' => $this->getGrowth($wooReportSalesData['totalRefunds'], $result['totalRefunds']),
            'newCustomers' => $this->getGrowth($wooReportSalesData['newCustomers'], $result['newCustomers']),
            'repeatCustomers' => $this->getGrowth($wooReportSalesData['repeatCustomers'], $result['repeatCustomers']),
            'failedOrders' => $this->getGrowth($wooReportSalesData['failedOrders'], $result['failedOrders']),
            'taxesCollected' => $this->getGrowth($wooReportSalesData['taxesCollected'], $result['taxesCollected']),
        ];
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    private function getReportSalesData(DateTime $startDate, DateTime $endDate): array
    {
        $orderStats = new OrderStats();

        $query = [
            'before' => $endDate->format('Y-m-d H:i:s'),
            'after' => $startDate->format('Y-m-d H:i:s'),
            'interval' => 'year',
            'fields' => [
                'orders_count',
                'gross_sales',
                'net_revenue',
                'refunds',
                'taxes'
            ]
        ];

        $results = $orderStats->get_data($query);

        if (is_wp_error($results)) {
            throw new Exception(esc_attr($results->get_error_message()));
        }

        $customerStats = $this->getCustomerStats($startDate, $endDate);

        return [
            'grossSales' => $results->totals->gross_sales,
            'netSales' => $results->totals->net_revenue,
            'orderCount' => $results->totals->orders_count,
            'totalRefunds' => $results->totals->refunds,
            'newCustomers' => $customerStats->newCustomerCount,
            'repeatCustomers' => $customerStats->returningCustomerCount,
            'failedOrders' => $this->getFailedOrderCount($startDate, $endDate),
            'taxesCollected' => $results->totals->taxes,
        ];
    }
}
