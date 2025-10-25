<?php

/**
 * PaymentGatewayCheckoutBlockSupport
 *
 * This file contains a payment gateway class for WooCommerce to integrate with Stripe on checkout block
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use StellarPay\Core\EnqueueScript;
use StellarPay\Integrations\Stripe\StripeErrorMessage;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\FilterPaymentTokensByPaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\HandlesStripeElementData;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;

/**
 * Class PaymentGatewayCheckoutBlockSupport
 *
 * @since 1.0.0
 */
class PaymentGatewayCheckoutBlockSupport extends AbstractPaymentMethodType
{
    use HandlesStripeElementData;

    /**
     * Payment method id.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $name;

    /**
     * Payment gateway object.
     *
     * @since 1.0.0
     */
    private ?PaymentGateway $paymentGateway = null;

    /**
     * Account repository.
     *
     * @since 1.0.0
     */
    private AccountRepository $accountRepository;

    /**
     * Options repository.
     *
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(AccountRepository $accountRepository, SettingRepository $settingRepository)
    {
        $this->accountRepository = $accountRepository;
        $this->name = Constants::GATEWAY_ID;
        $this->settingRepository = $settingRepository;
    }

    /**
     * This function initializes the payment method.
     *
     * @since 1.0.0
     */
    public function initialize(): void
    {
        add_action(
            'woocommerce_blocks_enqueue_checkout_block_scripts_before',
            static function () {
                add_filter('woocommerce_get_customer_payment_tokens', [FilterPaymentTokensByPaymentGatewayMode::class,
                    'woocommerceGetCustomerPaymentTokensFilterCallback'
                ]);
            }
        );

        add_action(
            'woocommerce_blocks_enqueue_checkout_block_scripts_end',
            static function () {
                remove_filter('woocommerce_get_customer_payment_tokens', [FilterPaymentTokensByPaymentGatewayMode::class,
                    'woocommerceGetCustomerPaymentTokensFilterCallback'
                ]);
            }
        );
    }

    /**
     * This function returns asset ids like script and style file handles.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function get_payment_method_script_handles(): array // phpcs:ignore
    {
        $scriptId = 'stripe-checkout-block-integration';

        $script = new EnqueueScript($scriptId, "/build/$scriptId.js");
        $script->registerTranslations()
            ->loadInFooter()
            ->register();

        return $this->accountRepository->isLiveModeConnected() ? [$script->getScriptId()] : [];
    }

    /**
     * This function returns the payment method data.
     *
     * Payment method data will be accessible on the client side.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function get_payment_method_data(): array // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $data =  [
            'title' => $this->settingRepository->getPaymentGatewayTitle(),
            'features' => $this->getPaymentMethod()->supports,
            'showSavedCards' => $this->canShowSavedCards(),
            'showSaveOption' => $this->isSupportSavedCardFeature(),
            'stripeErrorMessages' => StripeErrorMessage::getErrorMessageList()
        ];

        if ($stripeElementData = $this->getStripeElementData()) {
            $data = array_merge($data, $stripeElementData);
        }

        return $data;
    }

    /**
     * This function returns the payment method class object.
     *
     * @since 1.0.0
     */
    public function getPaymentMethod(): PaymentGateway
    {
        if ($this->paymentGateway) {
            return $this->paymentGateway;
        }

        $registeredGateways = WC()->payment_gateways()->payment_gateways();
        $this->paymentGateway = $registeredGateways[$this->name];

        return $this->paymentGateway;
    }

    /**
     * This function checks if saved cards can be shown.
     *
     * @todo implement setting to allow admin to toggle saved card feature on checkout block.
     *
     * @since 1.0.0
     */
    private function canShowSavedCards(): bool
    {
        return true;
    }

    /**
     * This function checks if the saved card feature is supported.
     *
     * @since 1.0.0
     */
    private function isSupportSavedCardFeature(): bool
    {
        return in_array('tokenization', $this->getPaymentMethod()->supports, true);
    }
}
