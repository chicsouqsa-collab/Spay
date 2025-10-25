<?php

/**
 * This class is responsible to update payment method domain when the website migrates.
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;

/**
 * @since 1.0.0
 */
class AddPaymentMethodDomain
{
    /**
     * @since 1.0.0
     */
    protected SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    protected AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    protected CreateAndValidatePaymentMethodDomain $createAndValidatePaymentMethodDomain;

    /**
     * @since 1.0.0
     */
    public function __construct(
        SettingRepository $settingRepository,
        AccountRepository $accountRepository,
        CreateAndValidatePaymentMethodDomain $createAndValidatePaymentMethodDomain
    ) {
        $this->settingRepository = $settingRepository;
        $this->accountRepository = $accountRepository;
        $this->createAndValidatePaymentMethodDomain = $createAndValidatePaymentMethodDomain;
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws InvalidPropertyException
     * @throws Exception
     */
    public function __invoke(): void
    {
        $paymentGatewayMode = $this->settingRepository->getPaymentGatewayMode();
        $paymentMethodDomainDTO = $this->accountRepository->getPaymentMethodDomain($paymentGatewayMode);
        if (! $paymentMethodDomainDTO) {
            return;
        }

        $domain = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($paymentMethodDomainDTO->getDomain() === $domain) {
            return;
        }

        $invokable = $this->createAndValidatePaymentMethodDomain;
        $invokable($paymentGatewayMode);
    }
}
