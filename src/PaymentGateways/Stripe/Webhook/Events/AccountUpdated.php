<?php

/**
 * This class is responsible for processing the account.updated event from Stripe.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Webhook\Events;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\AccountDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\AccountDTO as StripeAccount;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\AccountEventDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\PaymentGateways\Stripe\Services\AccountService;
use StellarPay\Core\Webhooks\EventProcessor;

/**
 * Class AccountUpdated
 *
 * @since 1.0.0
 */
class AccountUpdated extends EventProcessor
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
    public function __construct(
        EventResponse $eventResponse,
        AccountRepository $accountRepository,
        AccountService $accountService
    ) {
        $this->accountRepository = $accountRepository;
        $this->accountService = $accountService;

        parent::__construct($eventResponse);
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     * @throws Exception
     * @throws BindingResolutionException
     */

    public function processEvent(): EventResponse
    {
        $eventDTO = $this->getEventDTO();
        $isAccountConnected = Webhook::$paymentGatewayMode->isLive()
            ? $this->accountRepository->isLiveModeConnected()
            : $this->accountRepository->isTestModeConnected();

        // Exit if an account is not connected.
        if (! $isAccountConnected) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }


        $accountEvent = AccountEventDTO::fromEvent($eventDTO);
        $stripeAccount = $accountEvent->getAccount();
        $isAccountSettingsUpdated = $accountEvent->isAccountSettingsUpdated();

        if (! $isAccountSettingsUpdated) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::UNPROCESSABLE())
                ->ensureResponse();
        }

        $this->updateAccount($stripeAccount);

        return $this->eventResponse
        ->setWebhookEventRequestStatus(WebhookEventRequestStatus::SUCCEEDED())
        ->ensureResponse();
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     * @throws Exception
     */
    private function updateAccount(StripeAccount $stripeAccount): void
    {
        $stripeAccountDetailOptionName = $this->accountRepository->getStripeAccountOptionName(Webhook::$paymentGatewayMode);
        $accountOptionValue = get_option($stripeAccountDetailOptionName);
        $savedAccount = AccountDTO::fromArray($accountOptionValue);

        // Stripe account id should match.
        if ($savedAccount->getAccountId() !== $stripeAccount->getAccountId()) {
            return;
        }

        $logo = $stripeAccount->getLogoImageId();
        $icon = $stripeAccount->getIconImageId();

        $newData = [
            'account_country' => $stripeAccount->getAccountCountry(),
            'account_currency' => $stripeAccount->getAccountCurrency(),
            'statement_descriptor' => $stripeAccount->getStatementDescriptor(),
            'account_name' => $stripeAccount->getAccountName(),
            'account_logo' => $logo ? $this->accountService->getAccountFile($logo) : '',
            'account_icon' => $icon ? $this->accountService->getAccountFile($icon) : '',
        ];

        $accountOptionValue = array_merge($accountOptionValue, $newData);

        // Stripe allows limited edits to a live mode test account.
        // We should sync change if a live mode account is connected as a test account.
        if (Webhook::$paymentGatewayMode->isLive() && $this->accountRepository->isBothModesUseSameStripeAccount()) {
            $this->syncConnectLiveModeTestAccount($newData);
        }

        $this->accountRepository->saveAccount(
            AccountDTO::fromArray($accountOptionValue),
            Webhook::$paymentGatewayMode
        );
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    private function syncConnectLiveModeTestAccount(array $newData): void
    {
        $stripeAccountDetailOptionName = $this->accountRepository->getStripeAccountOptionName(PaymentGatewayMode::test());
        $accountOptionValue = get_option($stripeAccountDetailOptionName);
        $accountOptionValue = array_merge($accountOptionValue, $newData);

        $this->accountRepository->saveAccount(
            AccountDTO::fromArray($accountOptionValue),
            PaymentGatewayMode::test()
        );
    }
}
