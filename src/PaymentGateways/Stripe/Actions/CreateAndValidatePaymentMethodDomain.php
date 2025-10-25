<?php

/**
 * This class is action that perform the following tasks:
 *
 * - Register payment method domain on Stripe
 * - Validate payment method domain on Stripe
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Services\AccountService;

/**
 * Class CreateAndValidatePaymentMethodDomain
 *
 * @since 1.0.0
 */
class CreateAndValidatePaymentMethodDomain
{
    /**
     * @since 1.0.0
     */
    private AccountService $accountService;

    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(AccountService $accountService, AccountRepository $accountRepository)
    {
        $this->accountService = $accountService;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @since 1.0.0
     */
    public function __invoke(PaymentGatewayMode $paymentGatewayMode): void
    {
        $paymentMethodDomainDTO = $this->accountService->registerDomain();
        $paymentMethodDomainDTO = $this->accountService->validateDomain($paymentMethodDomainDTO->getId());

        $this->accountRepository->savePaymentMethodDomain($paymentMethodDomainDTO, $paymentGatewayMode);
    }
}
