<?php

/**
 * HandlesStripeAccount Trait.
 *
 * This trait is responsible for handling the Stripe account related logic.
 *
 * @package StellarPay\Integrations\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Vendors\Stripe\Account as StripeApiAccountModel;
use StellarPay\Vendors\Stripe\Balance;
use StellarPay\Vendors\Stripe\Collection;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;
use StellarPay\Vendors\Stripe\File;
use StellarPay\Vendors\Stripe\PaymentMethodDomain;
use StellarPay\Vendors\Stripe\Payout;
use StellarPay\Vendors\Stripe\Radar\EarlyFraudWarning;
use StellarPay\Vendors\Stripe\StripeClient;

/**
 * Trait HandlesStripeAccount
 *
 * @since 1.0.0
 * @property StripeClient $client
 */
trait HandlesStripeAccount
{
    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getAccount(string $stripeAccountId): StripeApiAccountModel
    {
        try {
            return $this->client->accounts->retrieve($stripeAccountId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @throws StripeAPIException
     */
    public function getBalance(): Balance
    {
        try {
            return $this->client->balance->retrieve();
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getUpcomingPayout(): Collection
    {
        try {
            return $this->client->payouts->all(['status' => Payout::STATUS_PENDING, 'limit' => 100 ]); // @phpstan-ignore-line
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getDisputes(): Collection
    {
        try {
            return $this->client->disputes->all(['limit' => 100 ]); // @phpstan-ignore-line
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     * @return Collection<EarlyFraudWarning>
     */
    public function getEarlyFraudWarnings(array $params = []): Collection
    {
        try {
            $params = array_merge($params, ['limit' => 100 ]);
            return $this->client->radar->earlyFraudWarnings->all($params); // @phpstan-ignore-line
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getAccountFile(string $imageId): File
    {
        try {
            return $this->client->files->retrieve($imageId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     *
     * @throws StripeAPIException
     */
    public function registerDomain(string $url, bool $enabled = true): PaymentMethodDomain
    {
        try {
            return $this->client->paymentMethodDomains->create([
                'domain_name' => $url,
                'enabled' => $enabled
            ]);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function validateDomain(string $paymentMethodDomainId): PaymentMethodDomain
    {
        try {
            return $this->client->paymentMethodDomains->validate($paymentMethodDomainId);
        } catch (ApiErrorException $e) {
            throw new StripeAPIException($e); // phpcs:ignore
        }
    }
}
