<?php

/**
 * This class is responsible to save stripe account email to a contact list on gateway server.
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Client;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;

use function StellarPay\Core\remote_get;

/**
 * @since 1.0.0
 */
class OptInStripeAccountEmail
{
    public const CRON_JOB_NAME = 'stellarpay_opt_in_stripe_account_email';

    /**
     * @since 1.0.0
     */
    protected SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    protected Client $client;

    /**
     * @since 1.0.0
     */
    protected AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        AccountRepository $accountRepository,
        SettingRepository $settingRepository,
        Client $client
    ) {
        $this->accountRepository = $accountRepository;
        $this->settingRepository = $settingRepository;
        $this->client = $client;
    }

    /**
     * @throws Exception
     * @throws InvalidPropertyException
     */
    public function __invoke(string $email)
    {
        if ($this->accountRepository->isLiveModeConnected()) {
            remote_get(
                $this->client->getStripeAccountOptedInUrl(
                    PaymentGatewayMode::live(),
                    $email
                ),
                ['blocking' => false]
            );
        }
    }
}
