<?php

/**
 * SaveConnectedAccount
 *
 * This class is responsible for saving the connected Stripe account.
 *
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Actions;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\AccountDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Services\AccountService;

/**
 * SaveConnectedAccount
 *
 * @since 1.0.0
 */
class SaveConnectedAccount
{
    /**
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    private AccountService $accountService;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(AccountRepository $accountRepository, AccountService $accountService)
    {

        $this->accountRepository = $accountRepository;
        $this->accountService = $accountService;
    }

    /**
     * @since 1.1.0 Move exception handling logic to onboarding controller.
     * @since 1.0.0
     *
     * @throws StripeAPIException
     * @throws InvalidPropertyException|Exception
     */
    public function __invoke(array $data, PaymentGatewayMode $paymentGatewayMode): bool
    {
        // Get the account from Stripe.
        // It will verify the Stripe account.
        $stripeAccount = $this->accountService->getAccount($data['stripe_account_id']);

        // Stripe account images.
        $logo = $stripeAccount->getLogoImageId();
        $icon = $stripeAccount->getIconImageId();

        $accountData =  [
            'connection_type' => $paymentGatewayMode->getId(),
            'account_id' => $stripeAccount->getAccountId(),
            'account_country' => $stripeAccount->getAccountCountry(),
            'account_currency' => $stripeAccount->getAccountCurrency(),
            'statement_descriptor' => $stripeAccount->getStatementDescriptor(),
            'account_name' => $stripeAccount->getAccountName(),
            'secret_key' => $data['stripe_access_token'],
            'publishable_key' => $data['stripe_publishable_key'],
            'account_logo' => $logo ? $this->accountService->getAccountFile($logo) : '',
            'account_icon' => $icon ? $this->accountService->getAccountFile($icon) : '',
            'has_controller' => $stripeAccount->hasController(),
        ];

        // Stripe allows limited edits to a live mode test account.
        // We should match data if a live mode account is connected as a test account.
        if ($paymentGatewayMode->isTest()) {
            $liveAccount = $this->accountRepository->getAccount(PaymentGatewayMode::live());

            if ($stripeAccount->getAccountId() === $liveAccount->getAccountId()) {
                $accountData = array_merge(
                    $accountData,
                    [
                        'account_country' => $liveAccount->getAccountCountry(),
                        'account_currency' => $liveAccount->getAccountCurrency(),
                        'statement_descriptor' => $liveAccount->getStatementDescriptor(),
                        'account_name' => $liveAccount->getAccountName(),
                        'account_logo' => $liveAccount->getAccountLogo(),
                        'account_icon' => $liveAccount->getAccountIcon(),
                    ]
                );
            }
        }

        // Save the account to the database.
        return $this->accountRepository->saveAccount(AccountDTO::fromArray($accountData), $paymentGatewayMode);
    }
}
