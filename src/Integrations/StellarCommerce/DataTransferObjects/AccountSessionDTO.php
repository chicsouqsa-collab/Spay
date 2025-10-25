<?php

/**
 * Account session data transfer object.
 *
 * @package StellarPay\Integrations\StellarCommerce\DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\StellarCommerce\DataTransferObjects;

/**
 * Class AccountSessionDTO
 *
 * @since 1.0.0
 */
class AccountSessionDTO
{
    /**
     * @var string
     */
    public string $publishableKey;

    /**
     * @since 1.0.0
     */
    public string $accountSessionClientSecret;

    /**
     * @since 1.0.0
     */
    public int $expiresAt;

    /**
     * @since 1.0.0
     */
    public bool $liveMode;

    /**
     * @since 1.0.0
     */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->publishableKey = $data['publishable_key'];
        $self->accountSessionClientSecret = $data['client_secret'];
        $self->expiresAt = absint($data['expires_at']);
        $self->liveMode = 'true' ===  $data['live_mode'];

        return $self;
    }
}
