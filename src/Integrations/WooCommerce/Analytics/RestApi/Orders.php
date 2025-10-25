<?php

/**
 * This class handles api request for "stellarpay/v1/orders" endpoint.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Analytics\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Analytics\RestApi;

use Exception;
use stdClass;
use StellarPay\Integrations\WooCommerce\Analytics\RestApi\DataStores\OrderStats;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.0.0
 */
class Orders extends StatsApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'orders';

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        $this->setupDatesByPeriod($request);

        $orderStats = new OrderStats();
        $isUsingDayPeriod = 'day' === $request->get_param('period');

        $query = [
            'before' => $this->endDate->format('Y-m-d H:i:s'),
            'after' => $this->startDate->format('Y-m-d H:i:s'),
            'interval' => $isUsingDayPeriod ? 'hour' : 'day',
            'fields' => ['orders_count','gross_sales', 'net_revenue',]
        ];

        $results = $orderStats->get_data($query);

        $this->validateResults($results);

        $currentPage = $results->page_no;
        $pagesCount = $results->pages;
        $data = [];

        $this->addData($results, $data, $request);

        if ($pagesCount > 0) {
            do {
                $newQuery = $query;
                $newQuery['page'] = ++$currentPage;
                $results = $orderStats->get_data($newQuery);

                $this->validateResults($results);

                $this->addData($results, $data, $request);
            } while ($pagesCount > $currentPage);
        }

        if ($isUsingDayPeriod && count($data) > 26) {
            $data = array_slice($data, 0, 26);
        }

        return rest_ensure_response($data);
    }

    /**
     * @since 1.0.0
     *
     * @param stdClass|WP_Error $results Query result.
     *
     */
    private function addData($results, array &$data, WP_REST_Request $request): void
    {
        $intervals = $results->intervals;

        foreach ($intervals as $interval) {
            $date = 'day' === $request->get_param('period')
                ? $interval['date_start']
                : current(explode(' ', $interval['date_start']));

            $data[] = [
                'date' => $date,
                'ordersCount' => $interval['subtotals']->orders_count,
                'grossSales' => $interval['subtotals']->gross_sales,
                'netSales' => $interval['subtotals']->net_revenue,
            ];
        }
    }

    /**
     * @since 1.0.0
     *
     * @param WP_Error|stdClass $results
     *
     * @throws \StellarPay\Core\Exceptions\Primitives\Exception
     */
    private function validateResults($results): void
    {
        if (is_wp_error($results)) {
            throw new \StellarPay\Core\Exceptions\Primitives\Exception(
                esc_attr($results->get_error_message())
            );
        }
    }
}
