<?php

/**
 * This class handles api request for "stellarpay/v1/leaderboards" endpoint.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Analytics\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Analytics\RestApi;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use DateTime;
use Exception;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @since 1.0.0
 */
class Leaderboards extends StatsApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'leaderboards';

    /**
     * @since 1.0.0
     */
    private array $commonQuery = [
        'page' => 1,
        'per_page' => 5,
    ];

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        $this->setupDatesByPeriod($request);

        return rest_ensure_response([
            $this->getProductsData($this->startDate, $this->endDate),
            $this->getCustomersData($this->startDate, $this->endDate),
        ]);
    }

    /**
     * @since 1.0.0
     */
    protected function getProductsData(DateTime $startDate, DateTime $endDate): array
    {
        $formatedData = $this->getProducts($startDate, $endDate);

        $growth = 0;
        if ($formatedData) {
            // Sort products by net revenue.
            $netRevenueColumn = array_column($formatedData, 'netRevenue');
            array_multisort($netRevenueColumn, SORT_DESC, $formatedData);

            // Calculate growth.
            $previousPeriodFormatedData = $this->getProducts($this->lastStartDate, $this->lastEndDate);
            $growth = $this->getGrowth(
                $previousPeriodFormatedData
                    ? array_sum(array_column($previousPeriodFormatedData, 'netRevenue'))
                    : 0,
                array_sum(array_column($formatedData, 'netRevenue')),
            );
        }

        return [
            'id' => 'products',
            'rows' => $formatedData,
            'growth' => $growth
        ];
    }

    /**
     * @since 1.0.0
     */
    private function getCustomersData(DateTime $startDate, DateTime $endDate): array
    {
        $formatedData = $this->getCustomers($startDate, $endDate);

        $growth = 0;
        if ($formatedData) {
            // Sort products by net revenue.
            $totalSpendAmountColumn = array_column($formatedData, 'totalSpendAmount');
            array_multisort($totalSpendAmountColumn, SORT_DESC, $formatedData);

            // Calculate growth.
            $previousPeriodFormatedData = $this->getCustomers($this->lastStartDate, $this->lastEndDate);
            $growth = $this->getGrowth(
                $previousPeriodFormatedData
                    ? array_sum(array_column($previousPeriodFormatedData, 'totalSpendAmount'))
                    : 0,
                array_sum(array_column($formatedData, 'totalSpendAmount')),
            );
        }

        return [
            'id' => 'customers',
            'rows' => $formatedData,
            'growth' => $growth
        ];
    }

    /**
     * @since 1.0.0
     */
    private function getProducts(DateTime $startDate, DateTime $endDate): array
    {
        $queryArgs = [
            'orderby' => 'net_revenue',
            'after' => $startDate->format('Y-m-d H:i:s'),
            'before' => $endDate->format('Y-m-d H:i:s'),
            'per_page' => $this->commonQuery['per_page'],
            'extended_info' => true,
        ];

        $productsDataStore = new ProductsDataStore();
        $productsData = $this->commonQuery['per_page'] > 0
            ? $productsDataStore->get_data($queryArgs)->data // @phpstan-ignore-line
            : [];

        $formatedData = [];

        foreach ($productsData as $productData) {
            $productId = $productData['product_id'];
            $extendedInfo = $productData['extended_info'];
            $categoryId = $extendedInfo['category_ids'] ? current($extendedInfo['category_ids']) : null;
            $thumbnailId = get_post_thumbnail_id($productId);

            $formatedData[$productId] = [
                'productName' => $extendedInfo['name'],
                'productImage' => $thumbnailId
                    ? wp_get_attachment_image_src($thumbnailId, 'thumbnail')[0]
                    : wc_placeholder_img_src(),
                'productLink' => get_edit_post_link($productId, 'edit'),
                'netRevenue' => $productData['net_revenue'],
                'categoryName' => '',
            ];

            $categoryDetail = get_term($categoryId, 'product_cat');
            if (! is_wp_error($categoryDetail)) {
                $formatedData[$productId]['categoryName'] = $categoryDetail->name;
            }
        }

        return $formatedData;
    }

    /**
     * @since 1.0.0
     */
    private function getCustomers(DateTime $startDate, DateTime $endDate): array
    {
        $queryArgs =  [
            'orderby' => 'total_spend',
            'order_after' => $startDate->format('Y-m-d H:i:s'),
            'order_before' => $endDate->format('Y-m-d H:i:s'),
            'per_page' => $this->commonQuery['per_page'],
        ];

        $customersDataStore = new CustomersDataStore();
        $customersData = $this->commonQuery['per_page'] > 0
            ? $customersDataStore->get_data($queryArgs)->data // @phpstan-ignore-line
            : [];

        $formatedData = [];

        foreach ($customersData as $customerData) {
            $customerId = $customerData['user_id'];
            $customerLink = $customerId
                ? esc_url_raw(admin_url('admin.php?page=wc-orders&status=all&_customer_user=' . $customerId))
                : esc_url_raw(admin_url('admin.php?page=wc-orders&status=all&s=' . $customerData['email']));

            $formatedData[$customerId] = [
                'customerName' => $customerData['name'],
                'customerEmail' => $customerData['email'],
                'customerProfileLink' => $customerLink,
                'orderCount' => $customerData['orders_count'],
                'totalSpendAmount' => $customerData['total_spend']
            ];
        }

        return $formatedData;
    }
}
