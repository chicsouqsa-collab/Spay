<?php

/**
 * This file is responsible for managing stripe client config data.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\RestApi;

use StellarPay\AdminDashboard\RestApi\StripeStats;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Actions\RemoveStripeAccountConnection;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PluginSetup\Migrations\StoreHomeUrlInOptionTable;
use StellarPay\RestApi\Endpoints\ApiRoute;
use WP_Error;
use WP_REST_Request;

use function StellarPay\Core\container;
use function wp_verify_nonce;

/**
 * Class DisconnectStripeAccount
 *
 * @since 1.0.0
 */
class DisconnectStripeAccount extends ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'disconnect-stripe-account';

    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(AccountRepository $accountRepository)
    {
        parent::__construct();

        $this->accountRepository = $accountRepository;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function register(): void
    {
        register_rest_route(
            $this->getNamespace(),
            $this->getEndpoint(),
            [
                'methods' => 'GET',
                'callback' => [$this, 'disconnectStripeAccount'],
                'permission_callback' => [$this, 'permissionCheck'],
                'args' => [
                    'mode' => [
                        'required' => true,
                        'type' => 'string',
                        'description' => esc_html__('Stripe connect mode', 'stellarpay'),
                        'validate_callback' => [$this, 'isValidMode']
                    ],
                    'nonce' => [
                        'required' => true,
                        'type' => 'string',
                    ]
                ],
            ]
        );
    }

    /**
     * @since 1.0.0
     */
    public function isValidMode($value): bool
    {
        return in_array($value, [PaymentGatewayMode::LIVE, PaymentGatewayMode::TEST], true);
    }

    /**
     * @since 1.0.0
     */
    public function permissionCheck(WP_REST_Request $request)
    {
        $status = parent::permissionCheck($request);

        if (is_wp_error($status)) {
            return $status;
        }

        $status = current_user_can('manage_options');

        if (! $status) {
            return new WP_Error(
                'stellarpay_rest_forbidden',
                esc_html__('You do not have permission to access this resource.', 'stellarpay'),
                ['status' => 403]
            );
        }

        $mode = $request->get_param('mode');
        $actionName = "stripe-$mode-mode-disconnect";
        if (wp_verify_nonce($request->get_param('nonce'), $actionName) === false) {
            return new WP_Error(
                'stellarpay_rest_invalid_nonce',
                esc_html__('Invalid nonce.', 'stellarpay'),
                ['status' => 403]
            );
        }

        return $status;
    }

    /**
     * @since 1.1.0 Implement "RemoveStripeAccountConnection" action
     * @since 1.0.0
     *
     * @throws BindingResolutionException|BindingResolutionException
     */
    public function disconnectStripeAccount(WP_REST_Request $request)
    {
        $paymentGatewayMode = new PaymentGatewayMode($request->get_param('mode'));
        $isAccountConnected = $paymentGatewayMode->isLive()
            ? $this->accountRepository->isLiveModeConnected()
            : $this->accountRepository->isTestModeConnected();

        if (! $isAccountConnected) {
            return new WP_Error(
                'stellarpay_rest_invalid_account',
                esc_html__('Invalid account.', 'stellarpay'),
                ['status' => 400]
            );
        }

        $this->accountRepository->deletePaymentMethodDomain($paymentGatewayMode);

        try {
            $removeStripeAccountConnection = container(RemoveStripeAccountConnection::class);
            $removeStripeAccountConnection($paymentGatewayMode);

            // Remove cached stripe stats results.
            if ($paymentGatewayMode->isLive()) {
                StripeStats::removeCache();
                container(StoreHomeUrlInOptionTable::class)->run();
            }
        } catch (\Exception $e) {
            return new WP_Error('stellarpay_failed_remove_account', $e->getMessage(), ['status' => 500]);
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * @since 1.0.0
     */
    public static function getDisconnectRequestUrl(PaymentGatewayMode $paymentGatewayMode): string
    {
        $data = [
            'mode' => $paymentGatewayMode->getId(),
            'nonce' => wp_create_nonce("stripe-$paymentGatewayMode-mode-disconnect"),
        ];

        return add_query_arg(
            $data,
            get_rest_url(null, 'stellarpay/v1/disconnect-stripe-account')
        );
    }
}
