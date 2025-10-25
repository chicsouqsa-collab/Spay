<?php

/**
 * Stripe API Service.
 *
 * This class is used to access Stripe account-related services.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use Exception;
use StellarPay\Core\ValueObjects\Money;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDomainDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\AccountDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\DisputeDTO;
use StellarPay\Vendors\Illuminate\Support\LazyCollection;

/**
 * Class AccountService
 *
 * @since 1.0.0
 */
class AccountService extends StripeApiService
{
    /**
     * This function fetches the account details from the Stripe API.
     *
     * @since 1.0.0
     *
     * @throws StripeAPIException
     */
    public function getAccount(string $stripeAccountId): AccountDTO
    {
        $stripeAccount = $this->httpClient->getAccount($stripeAccountId);

        return AccountDTO::fromStripeResponse($stripeAccount);
    }

    /**
     * @since 1.0.0
     */
    public function getBalance(): ?Money
    {
        $stripeBalance = $this->httpClient->getBalance();

        $totalAmount = 0;
        $availableAndPendingAmounts = [
            ...$stripeBalance->available,
            ...$stripeBalance->pending
        ];

        foreach ($availableAndPendingAmounts as $available) {
            $totalAmount += $available->amount; // @phpstan-ignore-line
        }


        if (! $totalAmount) {
            return null;
        }

        $currency = $stripeBalance->available[0]['currency'];

        return Money::fromMinorAmount($totalAmount, $currency);
    }

    /**
     * @since 1.0.0
     * @return Money
     */
    public function getUpcomingPayout(): ?Money
    {
        $payouts = $this->httpClient->getUpcomingPayout();

        if ($payouts->isEmpty()) {
            return null;
        }

        $totalAmount = array_reduce(
            $payouts->data,
            static function ($carry, $payout) {
                return $carry + $payout->amount;
            },
            0
        );

        return Money::fromMinorAmount($totalAmount, $payouts->first()->currency);
    }

    /**
     * @since 1.0.0
     *
     * @return LazyCollection<DisputeDTO>
     */
    public function getDisputes(): LazyCollection
    {
        $stripeDisputes = $this->httpClient->getDisputes();

        return new LazyCollection(function () use ($stripeDisputes) {
            foreach ($stripeDisputes as $stripeDispute) {
                yield DisputeDTO::fromStripeResponse($stripeDispute);
            }
        });
    }

    /**
     * Note: This function returns early fraud warning count for previous 30 days.
     *
     * @since 1.0.0
     */
    public function getEarlyFraudWarningsCount(): int
    {
        $localTimeInGMT = absint(get_gmt_from_date(current_time('mysql'), 'U'));
        $time = strtotime('- 30 days midnight', $localTimeInGMT);

        $earlyFraudWarnings = $this->httpClient->getEarlyFraudWarnings(['created' => [ 'gte' => $time ] ]);

        return $earlyFraudWarnings->count();
    }

    /**
     * Fetch the account file from Stripe.
     *
     * @since 1.0.0
     */
    public function getAccountFile(string $imageId): ?string
    {
        try {
            $fileLink = null;

            if ($imageId) {
                $file = $this->httpClient->getAccountFile($imageId);

                if ($file->links) {
                    $fileLink = $file->links->first();
                }
            }
        } catch (Exception $e) {
            // Skip the error.
        }

        return $fileLink->url ?? null; // @phpstan-ignore-line
    }

    /**
     * This function registers the website domain to Stripe.
     *
     * Note: Stripe itself prevents the addition of duplicate domain,
     *       for this reason we can register a domain multiple times without worrying about duplicate values.
     *
     * @since 1.0.0
     */
    public function registerDomain(): PaymentMethodDomainDTO
    {
        $domain = wp_parse_url(home_url(), PHP_URL_HOST);
        return PaymentMethodDomainDTO::fromStripeResponse(
            $this->httpClient->registerDomain($domain)
        );
    }

    /**
     * @since 1.0.0
     */
    public function validateDomain(string $paymentMethodDomainId): PaymentMethodDomainDTO
    {
        return PaymentMethodDomainDTO::fromStripeResponse($this->httpClient->validateDomain($paymentMethodDomainId));
    }
}
