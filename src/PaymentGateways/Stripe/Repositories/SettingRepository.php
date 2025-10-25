<?php

/**
 * Setting Repository.
 *
 * This class is responsible for providing settings related to the Stripe payment gateway.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Repositories;

use StellarPay\AdminDashboard\Repositories\OptionsRepository;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Class SettingRepository
 *
 * @since 1.0.0
 */
class SettingRepository
{
    /**
     * @since 1.0.0
     */
    private string $sendStripeReceiptKey;

    /**
     * @since 1.0.0
     */
    private string $saveCardsKey;

    /**
     * @since 1.0.0
     */
    private OptionsRepository $optionsRepository;

    /**
     * Class constructor.
     *
     * @todo OptionsRepository temporary set as dependency. We will refactor it when implement new payment gateway.
     *
     * @since 1.0.0
     */
    public function __construct(OptionsRepository $optionsRepository)
    {
        $this->sendStripeReceiptKey = dbMetaKeyGenerator('stripe_send_receipt', true);
        $this->saveCardsKey = dbMetaKeyGenerator('stripe_can_save_card', true);

        $this->optionsRepository = $optionsRepository;
    }

    /**
     * This method returns whether the Stripe payment gateway can send a receipt.
     *
     * @todo: add settings to the admin panel.
     *
     * @since 1.0.0
     */
    public function canSendStripeReceipts(): bool
    {
        return 'yes' === get_option($this->sendStripeReceiptKey, 'no');
    }

    /**
     * This method returns whether the Stripe payment gateway can save cards.
     *
     * @todo: add settings to the admin panel.
     *
     * @since 1.0.0
     *
     */
    public function canSaveCards(): bool
    {
        return 'yes' === get_option($this->saveCardsKey, 'no');
    }

    /**
     * Check if the test mode is active.
     *
     * @since 1.0.0
     */
    public function isTestModeActive(): bool
    {
        // 'true' = Test mode active
        // 'false' = Live mode active.
        return true === $this->optionsRepository->get('test-mode');
    }

    /**
     * @since 1.0.0
     */
    public function isLocalWebhookSecretKeyEnabled(): bool
    {
        return $this->getAll()['whsec-local-key-enabled'];
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentGatewayMode(): PaymentGatewayMode
    {
        return $this->isTestModeActive()
            ? PaymentGatewayMode::test()
            : PaymentGatewayMode::live();
    }

    /**
     * @since 1.3.0 use constant to get setting name
     * @since 1.0.0
     */
    public function getPaymentGatewayTitle(): string
    {
        return $this->getAll()[OptionsRepository::PAYMENT_OPTION_TITLE_GATEWAY_TITLE];
    }

    /**
     * @since 1.0.0
     */
    public function getPaymentStatementDescriptor(): string
    {
        return $this->getAll()['stripe-payment-statement-descriptor'];
    }

    /**
     * @since 1.0.0
     */
    public function isPaymentStatementDescriptorEnabled(): bool
    {
        return $this->getAll()['stripe-payment-statement-descriptor-enabled'];
    }

    /**
     * @since 1.0.0
     */
    public function getLocalWebhookSecretKey(): string
    {
        return $this->getAll()['whsec-local-key'];
    }

    /**
     * @since 1.0.0
     *
     * @return array|bool|string
     */
    public function get(string $key, $default = false)
    {
        return $this->optionsRepository->get($key, $default);
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     * @throws BindingResolutionException|Exception
     */
    public function getAll(): array
    {
        return $this->optionsRepository->getAll();
    }
}
