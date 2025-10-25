<?php

/**
 * Stripe data API.
 *
 * @package StellarPay/AdminDashboard/RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\Core\Cache;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\DisputeDTO;
use StellarPay\PaymentGateways\Stripe\Services\AccountService;
use StellarPay\RestApi\HandleMultipleApiRoutes;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 *
 * @todo handle not any data available condition.
 */
class StripeStats extends ApiRoute
{
    use HandleMultipleApiRoutes {
        registerAllRoutes as register;
    }

    /**
     * @since 1.0.0
     * @var string
     */
    protected string $endpoint = 'stripe';

    /**
     * @since 1.0.0
     */
    protected AccountService $accountService;

    /**
     * @since 1.0.0
     */
    protected ?Client $stripeClient;

    /**
     * @since 1.0.0
     */
    protected Cache $cache;

    /**
     * @since 1.0.0
     */
    public function __construct(AccountService $accountService, Cache $cache)
    {
        parent::__construct();

        $this->accountService = $accountService;
        $this->cache = $cache;
    }

    /**
     * Register the routes.
     *
     * @since 1.0.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) && current_user_can('manage_options');
    }

    /**
     * @since 1.0.0
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        // @todo we are fetch data from live stripe account. in future, it will fetch data from live or test account.
        $this->stripeClient = Client::getClient(PaymentGatewayMode::live());
        $this->accountService->setHttpClient($this->stripeClient);

        $routes = $this->getRoutes();
        $urlEnd = basename($request->get_route());

        $callback = $routes[$urlEnd]['mainCallback'];
        $cacheKey = 'stripe_stats_' . strtolower($callback);

        if ($result = $this->cache->get($cacheKey, true)) {
            return rest_ensure_response($result);
        }

        /* @var WP_REST_Response $result Response. */
        $invokable = $routes[$urlEnd]['mainCallback'];
        $result = $this->$invokable($request);

        $this->cache->set($cacheKey, $result->get_data(), DAY_IN_SECONDS, true);

        return $result;
    }

    /**
     * This function returns an array of route arguments.
     *
     * Note -
     * Each endpoint should result in a pre-defined format, otherwise the React component will not able to render it.
     * Review rest api controller to review a result format.
     *
     * @since 1.0.0
     */
    public function getRoutes(): array
    {
        return [
            'total-balance' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'processRequest',
                'mainCallback' => 'getTotalBalance',
            ],
            'next-payout-balance' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'processRequest',
                'mainCallback' => 'getNextPayoutBalance',
            ],
            'fraudulent-payments-count' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'processRequest',
                'mainCallback' => 'getEarlyFraudWarningsCount',
            ],
            'payment-disputes-count' => [
                'method' => WP_REST_Server::READABLE,
                'callback' => 'processRequest',
                'mainCallback' => 'getPaymentDisputesCount',
            ]
        ];
    }

    /**
     * @since 1.0.0
     * @return WP_REST_Response
     */
    public function getTotalBalance(): WP_REST_Response
    {
        $balance = $this->accountService->getBalance();

        return new WP_REST_Response([
            'value' => $balance ? $balance->getAmount() : 0,
        ], 200);
    }


    /**
     * @since 1.0.0
     */
    public function getNextPayoutBalance(WP_REST_Request $request): WP_REST_Response
    {
        $balance = $this->accountService->getUpcomingPayout();

        return new WP_REST_Response([
            'value' => $balance ? $balance->getAmount() : 0
        ], 200);
    }

    /**
     * @since 1.0.0
     */
    public function getEarlyFraudWarningsCount(WP_REST_Request $request): WP_REST_Response
    {
        $count = $this->accountService->getEarlyFraudWarningsCount();

        return new WP_REST_Response([
            'value' => $count
        ], 200);
    }


    /**
     * @since 1.0.0
     */
    public function getPaymentDisputesCount(WP_REST_Request $request): WP_REST_Response
    {
        $disputesCollection = $this->accountService->getDisputes();
        $numberOfUnAnsweredDisputes = 0;

        if ($disputesCollection->isNotEmpty()) {
            $disputeCountsByStatus = $disputesCollection->countBy(function (DisputeDTO $dispute) {
                return $dispute->getStatus();
            })->toArray();


            if (isset($disputeCountsByStatus[DisputeDTO::STATUS_NEEDS_RESPONSE])) {
                $numberOfUnAnsweredDisputes += $disputeCountsByStatus[DisputeDTO::STATUS_NEEDS_RESPONSE];
            }

            if (isset($disputeCountsByStatus[DisputeDTO::STATUS_WARNING_NEEDS_RESPONSE])) {
                $numberOfUnAnsweredDisputes += $disputeCountsByStatus[DisputeDTO::STATUS_WARNING_NEEDS_RESPONSE];
            }
        }

        return new WP_REST_Response([
            'value' => $numberOfUnAnsweredDisputes,
        ], 200);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public static function removeCache(): void
    {
        $stripeStats = container(self::class);
        $cache = container(Cache::class);

        foreach ($stripeStats->getRoutes() as $route) {
            $cacheKey = 'stripe_stats_' . strtolower($route['mainCallback']);
            $cache->delete($cacheKey, true);
        }
    }
}
