<?php

/**
 * This class used to manage stored thr Stripe account details.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Models
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;

/**
 * Class Account
 *
 * @since 1.0.0
 */
class AccountDTO
{
    /**
     * @since 1.0.0
     */
    private string $accountId;

    /**
     * @since 1.0.0
     */
    private ?string $accountName;

    /**
     * @since 1.0.0
     */
    private ?string $accountLogo;

    /**
     * @since 1.0.0
     */
    private ?string $accountIcon;

    /**
     * @since 1.0.0
     */
    private ?string $statementDescriptor;

    /**
     * @since 1.0.0
     */
    private ?string $accountCountry;

    /**
     * @since 1.0.0
     */
    private ?string $accountCurrency;

    /**
     * @since 1.0.0
     */
    private string $secretKey;

    /**
     * @since 1.0.0
     */
    private string $publishableKey;

    /**
     * @since 1.0.0
     */
    protected string $connectionType;

    /**
     * @since 1.0.0
     */
    protected bool $hasController;

    /**
     * @since 1.0.0
     *
     * @throws InvalidPropertyException
     */
    public static function fromArray(array $array): AccountDTO
    {
        $self = new self();

        $self->validate($array);

        $self->accountId = $array['account_id'];
        $self->accountCountry = $array['account_country'];
        $self->accountCurrency = $array['account_currency'];
        $self->accountName = $array['account_name'];
        $self->statementDescriptor = $array['statement_descriptor'];
        $self->accountLogo = $array['account_logo'] ?? null;
        $self->accountIcon = $array['account_icon'] ?? null;
        $self->secretKey = $array['secret_key'];
        $self->publishableKey = $array['publishable_key'];
        $self->connectionType = $array['connection_type'];
        $self->hasController = $array['has_controller'];


        return $self;
    }

    /**
     * @since 1.0.0
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'account_country' => $this->accountCountry,
            'account_currency' => $this->accountCurrency,
            'account_name' => $this->accountName,
            'statement_descriptor' => $this->statementDescriptor,
            'account_logo' => $this->accountLogo,
            'account_icon' => $this->accountIcon,
            'secret_key' => $this->secretKey,
            'publishable_key' => $this->publishableKey,
            'connection_type' => $this->connectionType,
            'has_controller' => $this->hasController,
        ];
    }

    /**
     * Validate array format.
     *
     * @since 1.0.0
     *
     * @param array $array
     *
     * @throws InvalidPropertyException
     */
    private function validate(array $array): void
    {
        $providedArgs = array_keys($array);

        $requiredArgs = [
            'account_id',
            'account_country',
            'account_name',
            'secret_key',
            'publishable_key',
            'connection_type'
        ];

        if (array_diff($requiredArgs, $providedArgs)) {
            throw new InvalidPropertyException(
                sprintf(
                    'To create a %1$s object, please provide valid: %2$s',
                    self::class,
                    implode(' , ', $requiredArgs) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                )
            );
        }
    }

    /**
     * @since 1.0.0
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountCountry(): string
    {
        return $this->accountCountry;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountCurrency(): string
    {
        return $this->accountCurrency;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountName(): string
    {
        return $this->accountName;
    }

    /**
     * @since 1.0.0
     */
    public function getStatementDescriptor(): string
    {
        return $this->statementDescriptor;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountLogo(): ?string
    {
        return $this->accountLogo;
    }

    /**
     * @since 1.0.0
     */
    public function getAccountIcon(): ?string
    {
        return $this->accountIcon;
    }

    /**
     * @since 1.0.0
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @since 1.0.0
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * @since 1.0.0
     */
    public function isConnected(): bool
    {
        return ! empty($this->secretKey);
    }

    /**
     * @since 1.0.0
     */
    public function isNotConnected(): bool
    {
        return ! $this->isConnected();
    }

    /**
     * @since 1.0.0
     */
    public function getConnectionType(): PaymentGatewayMode
    {
        return new PaymentGatewayMode($this->connectionType);
    }

    /**
     * @since 1.0.0
     */
    public function hasController(): bool
    {
        return $this->hasController;
    }

    /**
     * @since 1.0.0
     */
    public function isTestModeOnlyAccount(): bool
    {
        return $this->hasController() && $this->getConnectionType()->isTest();
    }
}
