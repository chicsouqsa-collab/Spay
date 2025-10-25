<?php

/**
 * This class uses as contract for rest api endpoint which returns stats for WooCommerce.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Analytics\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Analytics\RestApi;

use DateTime;
use Exception;
use StellarPay\RestApi\Endpoints\ApiRoute;
use WP_REST_Request;
use WP_REST_Server;

/**
 * @since 1.0.0
 */
abstract class StatsApiRoute extends ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $period;

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
     */
    protected DateTime $lastStartDate;

    /**
     * @since 1.0.0
     */
    protected DateTime $lastEndDate;

    /**
     * @since 1.0.0
     * @throws \StellarPay\Core\Exceptions\Primitives\Exception
     */
    public function register(): void
    {
        register_rest_route(
            $this->getNamespace(),
            $this->getEndpoint(),
            [
                'method' => WP_REST_Server::READABLE,
                'callback' => [$this, 'processRequest'],
                'permission_callback' => [$this, 'permissionCheck'],
                'args' =>
                    [
                        'period' => [
                            'type' => 'string',
                            'required' => true,
                            'enum' => $this->getPeriodEnum()
                        ],
                    ],
            ]
        );
    }

    /**
     * @since 1.0.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) && wc_rest_check_manager_permissions('settings', 'read');
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    protected function setupDatesByPeriod(WP_REST_Request $request): void
    {
        $timezone = wp_timezone();
        $this->period = $request->get_param('period');
        $isDayPeriod = 'day' === $this->period;
        $date = new DateTime('midnight', $timezone);

        if ($isDayPeriod) {
            $date = new DateTime('now', $timezone);
        }

        // Current date range.
        $this->startDate = clone $date;
        $this->endDate = clone $date;

        $this->startDate->modify('-1 ' . $this->period);
        $this->endDate->setTime(23, 59, 59);

        if ($isDayPeriod) {
            $this->endDate->setTime(absint($date->format('H')) + 1, 0, 0);
        }

        // Previous date range.
        $this->lastStartDate = clone $this->startDate;
        $this->lastEndDate = clone $this->endDate;

        $this->lastStartDate->modify('-1 ' . $this->period);
        $this->lastEndDate->modify('-1 ' . $this->period);
    }

    /**
     * @since 1.0.0
     */
    protected function getPeriodEnum(): array
    {
        return ['day', 'week', 'month'];
    }

    /**
     * @since 1.0.0
     *
     * @param float|int $previousValue
     * @param float|int $newValue
     */
    protected function getGrowth($previousValue, $newValue): float
    {
        if (! $previousValue) {
            $growthPercentage = empty($newValue) ? 0 : 100;
        } else {
            $growthPercentage = (($newValue - $previousValue) / $previousValue) * 100;
        }

        return round($growthPercentage, 2);
    }

    /**
     * @since 1.0.0
     */
    protected function getCacheKey(string $suffix): string
    {
        return implode(
            '_',
            [
                'stellarpay_woo_analytics',
                $this->endpoint,
                md5(wp_json_encode($suffix)),
            ]
        );
    }
}
