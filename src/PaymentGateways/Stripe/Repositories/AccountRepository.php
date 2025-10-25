<?php

/**
 * This class used to access account details.
 *
 * @package StellarPay\PaymentGateways\Stripe\Repositories
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Repositories;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\AccountDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PaymentMethodDomainDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDomainDTO as StripePaymentMethodDomainDTO;

use function StellarPay\Core\dbOptionKeyGenerator;

/**
 * Class Account
 *e
 * @since 1.0.0
 */
class AccountRepository
{
    /**
     * @since 1.0.0
     */
    private string $stripeAccountDetailsOptionNamePrefix;

    /**
     * @since 1.0.0
     */
    private string $stripeOnBoardingErrorOptionName;

    /**
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(SettingRepository $settingRepository)
    {
        $this->stripeAccountDetailsOptionNamePrefix = dbOptionKeyGenerator('stripe_account');
        $this->stripeOnBoardingErrorOptionName = dbOptionKeyGenerator('stripe_onboarding_error');

        $this->settingRepository = $settingRepository;
    }

    /**
     * This function gets the Stripe account option name.
     *
     * @since 1.0.0
     */
    public function getStripeAccountOptionName(PaymentGatewayMode $paymentGatewayMode): string
    {
        return $this->stripeAccountDetailsOptionNamePrefix . '_' . $paymentGatewayMode->getId();
    }

    /**
     * @since 1.0.0
     */
    public function getStripePaymentMethodDomainOptionName(PaymentGatewayMode $paymentGatewayMode): string
    {
        return dbOptionKeyGenerator('stripe_payment_method_domain') . '_' . $paymentGatewayMode->getId();
    }

    /**
     * This function saves the Stripe account details to the database.
     *
     * @since 1.0.0
     */
    public function saveAccount(AccountDTO $stripeAccount, PaymentGatewayMode $paymentGatewayMode): bool
    {
        return update_option(
            $this->getStripeAccountOptionName($paymentGatewayMode),
            $stripeAccount->toArray(),
            false
        );
    }

    /**
     * This function gets the Stripe account details from the database.
     *
     * @since 1.0.0
     * @throws InvalidPropertyException|Exception
     */
    public function getAccount(PaymentGatewayMode $paymentGatewayMode = null): AccountDTO
    {
        $paymentGatewayMode = $paymentGatewayMode ?? $this->settingRepository->getPaymentGatewayMode();
        $accountDetails = get_option($this->getStripeAccountOptionName($paymentGatewayMode), null);
        $exception = new Exception(esc_html(ucfirst($paymentGatewayMode->getId()) . ' Stripe account is not connected'));

        if (! $accountDetails) {
            throw $exception;
        }

        $account = AccountDTO::fromArray($accountDetails);

        // If a mode is provided, check if the account is connected in the provided mode.
        // If connected, return the account, otherwise return null.
        if ($account->isNotConnected()) {
            throw $exception;
        }

        return $account;
    }

    /**
     * This function deletes the Stripe account details from the database.
     *
     * @since 1.0.0
     */
    public function saveOnboardingError(string $error): bool
    {
        return update_option($this->stripeOnBoardingErrorOptionName, $error, false);
    }

    /**
     * @since 1.0.0
     */
    public function savePaymentMethodDomain(
        StripePaymentMethodDomainDTO $paymentMethodDomainDTO,
        PaymentGatewayMode $paymentGatewayMode
    ): bool {
        $savedData = get_option($this->getStripePaymentMethodDomainOptionName($paymentGatewayMode), []);

        $data = $this->formatPaymentMethodDomainDtoToArray($paymentMethodDomainDTO);
        $data = array_merge($savedData, $data);

        return update_option(
            $this->getStripePaymentMethodDomainOptionName($paymentGatewayMode),
            $data,
            false
        );
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException|BindingResolutionException
     */
    public function getPaymentMethodDomain(PaymentGatewayMode $paymentGatewayMode): ?PaymentMethodDomainDTO
    {
        $result = get_option($this->getStripePaymentMethodDomainOptionName($paymentGatewayMode), null);

        if ($result) {
            $result['domain'] = base64_decode($result['domain']);

            return PaymentMethodDomainDTO::fromArray($result);
        }
        return null;
    }

    /**
     * @since 1.0.0
     */
    public function deletePaymentMethodDomain(PaymentGatewayMode $paymentGatewayMode): bool
    {
        return delete_option($this->getStripePaymentMethodDomainOptionName($paymentGatewayMode));
    }

    /**
     * This function gets the Stripe onboarding error from the database.
     *
     * @since 1.0.0
     */
    public function getOnboardingError(): ?string
    {
        return get_option($this->stripeOnBoardingErrorOptionName, null);
    }

    /**
     * This function deletes the Stripe onboarding error from the database.
     *
     * @since 1.0.0
     */
    public function clearOnboardingError(): bool
    {
        return delete_option($this->stripeOnBoardingErrorOptionName);
    }

    /**
     * This function checks if the Stripe account is connected.
     *
     * Stripe should be connected in live mode.
     *
     * @since 1.0.0
     */
    public function isLiveModeConnected(): bool
    {
        try {
            $this->getAccount(PaymentGatewayMode::live());

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @since 1.0.0
     */
    public function isTestModeConnected(): bool
    {
        try {
            $this->getAccount(PaymentGatewayMode::test());

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @since 1.0.0
     */
    public function isBothModesUseSameStripeAccount(): bool
    {
        try {
            $liveAccount = $this->getAccount(PaymentGatewayMode::live());
            $testAccount = $this->getAccount(PaymentGatewayMode::test());

            return $liveAccount->getAccountId() === $testAccount->getAccountId();
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * This function deletes the Stripe account details from the database.
     *
     * @since 1.0.0
     */
    public function deleteAccount(PaymentGatewayMode $paymentGatewayMode): ?bool
    {
        return delete_option($this->getStripeAccountOptionName($paymentGatewayMode));
    }

    /**
     * @since 1.0.0
     */
    private function formatPaymentMethodDomainDtoToArray(StripePaymentMethodDomainDTO $paymentMethodDomainDTO): array
    {
        return [
            'id' => $paymentMethodDomainDTO->getId(),
            'enabled' => $paymentMethodDomainDTO->isEnabled(),
            'domain' => base64_encode($paymentMethodDomainDTO->getDomain()),
            'apple_pay' => $paymentMethodDomainDTO->getApplePayStatus(),
            'google_pay' => $paymentMethodDomainDTO->getGooglePayStatus(),
            'paypal' => $paymentMethodDomainDTO->getPayPalStatus()
        ];
    }
}
