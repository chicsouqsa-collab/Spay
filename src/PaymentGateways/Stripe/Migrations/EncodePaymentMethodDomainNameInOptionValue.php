<?php

/**
 * @package StellarPay\PaymentGateways\Stripe\Migrations
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Migrations;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

use function StellarPay\Core\container;

/**
 * @since 1.3.0
 */
class EncodePaymentMethodDomainNameInOptionValue extends Migration
{
    /**
     * @inheritdoc
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws InvalidPropertyException
     */
    public function run()
    {
        $accountRepository = container(AccountRepository::class);

        if (! $accountRepository->isLiveModeConnected()) {
            return;
        }

        $livePaymentMethod = $accountRepository->getPaymentMethodDomain(PaymentGatewayMode::LIVE());
        if ($livePaymentMethod) {
            $livePaymentMethodData = $livePaymentMethod->toArray();
            $livePaymentMethodData['domain'] = base64_encode($livePaymentMethod->getDomain());
            update_option($accountRepository->getStripePaymentMethodDomainOptionName(PaymentGatewayMode::LIVE()), $livePaymentMethodData);
        }

        $testPaymentMethod = $accountRepository->getPaymentMethodDomain(PaymentGatewayMode::TEST());
        if ($testPaymentMethod) {
            $testPaymentMethodData = $testPaymentMethod->toArray();
            $testPaymentMethodData['domain'] = base64_encode($testPaymentMethod->getDomain());
            update_option($accountRepository->getStripePaymentMethodDomainOptionName(PaymentGatewayMode::TEST()), $testPaymentMethodData);
        }
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function id(): string
    {
        return 'encode-payment-method-domain-name-in-option-value';
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function title(): string
    {
        return esc_html__('Encode Payment Method Domain Name in Option Value', 'stellarpay');
    }

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function timestamp(): int
    {
        return strtotime('2025-01-20 20:11:00');
    }
}
