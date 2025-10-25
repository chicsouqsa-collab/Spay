<?php

/**
 * This class uses to access order stats from the 'wc_order_stats' database table.
 *
 * @package StellarPay\Integrations\WooCommerce\Analytics\RestApi\DataStores
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Analytics\RestApi\DataStores;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore;

/**
 * @since 1.0.0
 */
class OrderStats extends DataStore
{
    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();

        // The WooCommerce allows changing the date column for analytic reports.
        // Use the fixed column name for date.
        $this->date_column_name = 'date_created';
    }

    /**
     * @since 1.0.0
     */
    public static function getExcludedReportOrderStatuses(): array
    {
        $orderStatuses = self::get_excluded_report_order_statuses();
        $formatedOrderStatuses = array_map(function ($status) {
            return strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
        }, $orderStatuses);

        return $formatedOrderStatuses;
    }
}
