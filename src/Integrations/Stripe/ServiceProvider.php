<?php

/**
 * ServiceProvider
 *
 * This class is used to register bootstrap the StellarCommerce integration.
 *
 * @package StellarPay\Integrations\StellarCommerce
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

use function StellarPay\Core\container;

/**
 * Class ServiceProvider
 *
 * @since 1.0.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(Client::class, function () {
            $account = container(AccountRepository::class)->getAccount();

            return new Client(
                $account->getSecretKey(),
                $account->getAccountId()
            );
        });
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function boot(): void
    {
    }
}
