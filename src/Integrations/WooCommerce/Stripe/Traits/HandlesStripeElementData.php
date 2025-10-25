<?php

/**
 * HandlesStripeElementData
 *
 * This trait contains a function to return data which is used on the client side to render a Stripe element.
 * Data used to render the Stripe element on legacy checkout and checkout block.
 * Any changes to data should be tested on both checkout pages.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\AccountDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;

/**
 * Trait HandlesStripeElementData
 *
 * @since 1.0.0
 *
 * @property AccountRepository $accountRepository
 * @property SettingRepository $settingRepository
 * @method Client getStripeClient()
 */
trait HandlesStripeElementData
{
    /**
     * This function returns the data required to render the Stripe element on the client side.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function getStripeElementData(): array
    {
        try {
            $account = $this->getAccount();

            $paymentGatewayMode = $this->getPaymentGatewayMode();
            $options = $this->settingRepository->getAll();

            $data['stripeAccountId'] = $account->getAccountId();
            $data['stripePublishableKey'] = $account->getPublishableKey();
            $data['isStripeTestMode'] = $paymentGatewayMode->isTest();
            $data['stripeElementTheme'] = $options['payment-element-theme'];
            $data['stripePaymentElementLayout'] = $options['payment-element-layout'];
            $data['stripeElementAppearance'] = $options['payment-element-appearance'];
            $data['stripeConnectionMode'] = $paymentGatewayMode->getId();

            return $data;
        } catch (Exception $exception) {
            return [];
        }
    }

    /**
     * Get account
     *
     * @since 1.1.0
     */
    private function getAccount(): AccountDTO
    {
        if (! $subscription = MySubscriptionsEndpoint::getSubscriptionFromQueryVars()) {
            return $this->accountRepository->getAccount();
        }

        return $this->accountRepository->getAccount($subscription->paymentGatewayMode);
    }

    /**
     * Get Payment Gateway mode
     *
     * @since 1.1.0
     */
    private function getPaymentGatewayMode(): PaymentGatewayMode
    {
        if (! $subscription = MySubscriptionsEndpoint::getSubscriptionFromQueryVars()) {
            return $this->settingRepository->getPaymentGatewayMode();
        }

        return $subscription->paymentGatewayMode;
    }
}
