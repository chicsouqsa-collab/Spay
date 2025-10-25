<?php

/**
 * Client
 *
 * This class is used to manage the Stellar Commerce client.
 *
 * @package StellarPay\Integrations\StellarCommerce
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\StellarCommerce;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

use function StellarPay\Core\getNonceActionName;
use function StellarPay\Core\getNonceUrl;

/**
 * Class Client
 *
 * @since 1.0.0
 */
class Client
{
    /**
     * Account repository.
     *
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
        $this->accountRepository = $accountRepository;
    }

    /**
     * @since 1.0.0
     */
    public function getUrl(): string
    {
        $connectUrl = 'https://commerce.stellarwp.com';

        // Developer can use override the default connected URL.
        // This is useful for testing purposes.
        $connectUrl = defined('STELLARPAY_STRIPE_CONNECT_URL') && STELLARPAY_STRIPE_CONNECT_URL
            ? sanitize_url(untrailingslashit(STELLARPAY_STRIPE_CONNECT_URL))
            : $connectUrl;

        return untrailingslashit($connectUrl);
    }

    /**
     * @since 1.0.0
     */
    public function getStripeConnectUrl(): string
    {
        return $this->getUrl() . '/stripe/connect';
    }

    /**
     * @since 1.0.0
     */
    public function getRedirectUrlNonceActionName(PaymentGatewayMode $paymentGatewayMode): string
    {
        return getNonceActionName("stripe-{$paymentGatewayMode}-mode-onboarding");
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException|Exception
     */
    public function getStripeOnBoardingUrl(PaymentGatewayMode $paymentGatewayMode): string
    {
        $connectUrl = $this->getStripeConnectUrl();
        $secureUrl = getNonceUrl(
            $this->getRedirectUrlNonceActionName($paymentGatewayMode),
            add_query_arg(
                ['page' => 'stellarpay',],
                admin_url('admin.php')
            )
        );

        $queryData = [
            'stripe_action' => 'connect',
            'mode' => $paymentGatewayMode->getId(),
            'return_url' => rawurlencode(esc_url_raw($secureUrl)),
            'website_url' => $this->getHomeUrl(),
        ];

        if ($paymentGatewayMode->isTest()) {
            $account = $this->accountRepository->getAccount(PaymentGatewayMode::live());
            $queryData['request_token'] = $account->getSecretKey();
        }

        return add_query_arg($queryData, $connectUrl);
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException|Exception
     */
    public function getStripeDisconnectRequestUrl(PaymentGatewayMode $paymentGatewayMode): ?string
    {
        $account = $this->accountRepository->getAccount($paymentGatewayMode);

        $queryData = [
            'stripe_action' => 'disconnect',
            'mode' => $paymentGatewayMode->getId(),
            'request_token' => $account->getSecretKey(),
            'website_url' => $this->getHomeUrl(),
        ];

        return add_query_arg($queryData, $this->getStripeConnectUrl());
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException|Exception
     */
    public function getCreateAccountSessionUrl(PaymentGatewayMode $mode): ?string
    {
        $account = $this->accountRepository->getAccount($mode);

        $queryData = [
            'stripe_action' => 'account-session',
            'mode' => $mode->getId(),
            'request_token' => $account->getSecretKey(),
            'website_url' => $this->getHomeUrl(),
        ];

        return add_query_arg($queryData, $this->getStripeConnectUrl());
    }

    /**
     * @throws Exception
     * @throws InvalidPropertyException
     */
    public function getStripeAccountOptedInUrl(PaymentGatewayMode $mode, string $email): ?string
    {
        if ($mode->isTest()) {
            throw new Exception('Only live mode is supported.');
        }

        $account = $this->accountRepository->getAccount($mode);

        return add_query_arg(
            [
                'website_url' => $this->getHomeUrl(),
                'action' => 'add-contact',
                'mode' => $mode->getId(),
                'email' => rawurlencode($email),
                'request_token' => $account->getSecretKey()
            ],
            $this->getUrl() . '/activecampaign'
        );
    }

    /**
     * @since 1.0.0
     */
    public function getHomeUrl(): string
    {
        return rawurlencode(esc_url_raw(get_home_url()));
    }
}
