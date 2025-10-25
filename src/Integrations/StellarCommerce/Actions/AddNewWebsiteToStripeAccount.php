<?php

/**
 * @package StellarPay\Integrations\StellarCommerce\Actions
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\StellarCommerce\Actions;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Traits\RemoteHelpers;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Client;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

/**
 * @since 1.3.0
 */
class AddNewWebsiteToStripeAccount
{
    use RemoteHelpers;

    /**
     * @since 1.3.0
     */
    protected AccountRepository $accountRepository;

    /**
     * @since 1.3.0
     */
    protected Client $client;

    /**
     * @since 1.3.0
     */
    public function __construct(Client $client, AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
        $this->client = $client;
    }

    /**
     * @since 1.3.0
     *
     * @throws Exception
     * @throws InvalidPropertyException
     */
    public function __invoke(PaymentGatewayMode $paymentGatewayMode): bool
    {
        if ($paymentGatewayMode->isLive() && $this->accountRepository->isLiveModeConnected()) {
            $account = $this->accountRepository->getAccount($paymentGatewayMode);
        } elseif ($paymentGatewayMode->isTest() && $this->accountRepository->isTestModeConnected()) {
            $account = $this->accountRepository->getAccount($paymentGatewayMode);
        } else {
            return false;
        }

        $requestURL = add_query_arg(
            [
                'mode' => $paymentGatewayMode->getId(),
                'request_token' => $account->getSecretKey(),
                'website_url' => $this->client->getHomeUrl()
            ],
            $this->client->getUrl() . '/stripe/add-new-website'
        );

        $this->remoteGet($requestURL, ['blocking' => false]);

        return true;
    }
}
