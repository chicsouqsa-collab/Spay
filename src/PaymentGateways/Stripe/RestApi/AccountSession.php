<?php

/**
 * This class is responsible for handling account session.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\RestApi;

use Exception;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Client;
use StellarPay\Integrations\StellarCommerce\DataTransferObjects\AccountSessionDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\RestApi\Endpoints\ApiRoute;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function StellarPay\Core\container;
use function StellarPay\Core\remote_get;

/**
 * Class AccountSession
 *
 * @since 1.0.0
 */
class AccountSession extends ApiRoute
{
    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'create-account-session';

    /**
     * @since 1.0.0
     */
    private Client $stellarCommerceClient;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->stellarCommerceClient = $client;
    }

    /**
     * Register the route.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        register_rest_route(
            $this->getNamespace(),
            $this->getEndpoint(),
            [
                'methods' => 'GET',
                'callback' => [$this, 'createAccountSession'],
                'permission_callback' => [$this, 'permissionCheck'],
            ]
        );
    }

    /**
     * Detach customer payment method.
     *
     * @return WP_Error|bool
     */
    public function permissionCheck(WP_REST_Request $request)
    {
        $check = parent::permissionCheck($request);

        if (is_wp_error($check)) {
            return $check;
        }

        if (! current_user_can('manage_options')) {
            return new WP_Error(
                'stellarpay_rest_forbidden',
                esc_html__('Sorry, you are not allowed to access this resource.', 'stellarpay'),
                ['status' => 401]
            );
        }

        $mode = $request->get_param('mode');
        $actionName = "stripe-$mode-mode-create-account-session";
        if (wp_verify_nonce($request->get_param('nonce'), $actionName) === false) {
            return new WP_Error(
                'stellarpay_rest_invalid_nonce',
                esc_html__('Invalid nonce.', 'stellarpay'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Create account session.
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function createAccountSession(WP_REST_Request $request)
    {
        $mode = new PaymentGatewayMode($request->get_param('mode'));

        // Fetch the account session.
        $response = remote_get($this->stellarCommerceClient->getCreateAccountSessionUrl($mode));

        if (is_wp_error($response)) {
            return rest_ensure_response(['success' => false]);
        }

        if (200 !== wp_remote_retrieve_response_code($response)) {
            return new WP_Error(
                'stellarpay_rest_invalid_response',
                esc_html__('Unable to create the Stripe account session. Please try later', 'stellarpay'),
                ['status' => 400]
            );
        }

        try {
            $result = json_decode(
                wp_remote_retrieve_body($response),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Exception $e) {
            return new WP_Error(
                'stellarpay_rest_invalid_response',
                esc_html__('Invalid response.', 'stellarpay'),
                ['status' => 400]
            );
        }

        $accountSession = AccountSessionDTO::fromArray($result);

        return rest_ensure_response([
            'platformPublishableKey' => $accountSession->publishableKey,
            'clientSecret' => $accountSession->accountSessionClientSecret,
        ]);
    }

    /**
     * Get the create account session request URL.
     *
     * @since 1.0.0
     * @throws BindingResolutionException|\StellarPay\Core\Exceptions\Primitives\Exception
     */
    public static function getCreateAccountSessionRequestUrl(PaymentGatewayMode $paymentGatewayMode): string
    {
        if ($paymentGatewayMode->isTest()) {
            $accountRepository = container(AccountRepository::class);
            $account = $accountRepository->getAccount(PaymentGatewayMode::test());

            $paymentGatewayMode = $account->isTestModeOnlyAccount()
                ? $paymentGatewayMode
                : PaymentGatewayMode::live();
        }

        $data = [
            'mode' => $paymentGatewayMode->getId(),
            'nonce' => wp_create_nonce("stripe-$paymentGatewayMode-mode-create-account-session"),
        ];

        return add_query_arg(
            $data,
            get_rest_url(null, 'stellarpay/v1/create-account-session')
        );
    }
}
