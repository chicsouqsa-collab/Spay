<?php

/**
 * Filter the saved payment tokens based on the payment gateway mode.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Actions
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Actions;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use WC_Payment_Token;

use function StellarPay\Core\container;
use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.1.0
 */
class FilterPaymentTokensByPaymentGatewayMode
{
    /**
     * @since 1.1.0
     */
    protected SettingRepository $settingRepository;

    /**
     * @since 1.1.0
     */
    protected ?PaymentGatewayMode $paymentGatewayMode;

    /**
     * @since 1.1.0
     */
    protected array $tokens;

    /**
     * @since 1.1.0
     */
    protected ?array $excludeTokens;

    /**
     * @since 1.1.0
     */
    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * @since 1.1.0
     */
    public function __invoke(array $tokens): array
    {
        $this->setTokens($tokens);

        return $this->getTokens();
    }

    /**
     * @since 1.1.0
     *
     * This function used to filter payment tokens on "woocommerce_get_customer_payment_tokens" filter hook.
     *
     * @throws BindingResolutionException
     */
    public static function woocommerceGetCustomerPaymentTokensFilterCallback(array $tokens): array
    {
        $self = container(self::class);

        return $self($tokens);
    }

    /**
     * Set payment gateway mode.
     *
     * @since 1.1.0
     */
    public function setMode(PaymentGatewayMode $paymentGatewayMode): self
    {
        $this->paymentGatewayMode = $paymentGatewayMode;

        return $this;
    }

    /**
     * Set tokens.
     *
     * @since 1.1.0
     */
    public function setTokens(array $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * Set exclude tokens.
     *
     * @since 1.1.0
     */
    public function setExcludeTokens(array $excludeTokens): self
    {
        $this->excludeTokens = $excludeTokens;

        return $this;
    }

    /**
     * Get tokens.
     *
     * @since 1.1.0
     */
    public function getTokens(): array
    {
        $paymentGatewayMode = $this->paymentGatewayMode ?? $this->settingRepository->getPaymentGatewayMode();
        $paymentMethodTokens = array_filter(
            $this->tokens,
            static function (WC_Payment_Token $token) use ($paymentGatewayMode) {
                // On the legacy checkout page, WooCommere display saved payment method token that belongs to payment gateway.
                // But on the checkout block, it displays all saved payment methods.
                // For this reason, we should filter bases on payment gateway to apply logic to own payment-gateway-related saved payment method tokens.
                if (Constants::GATEWAY_ID !== $token->get_gateway_id('edit')) {
                    return true;
                }

                $paymentTokenPaymentGatewayMode = new PaymentGatewayMode(
                    $token->get_meta(dbMetaKeyGenerator('payment_method_mode', true))
                );

                return $paymentTokenPaymentGatewayMode->match($paymentGatewayMode);
            }
        );

        if (! $paymentMethodTokens) {
            return $paymentMethodTokens;
        }

        // Check whether the customer has a default payment method token,
        // If not, then we temporarily set from ours.
        $hasDefaultPaymentMethodToken = array_filter(
            $paymentMethodTokens,
            static function (WC_Payment_Token $token) {
                return $token->is_default();
            }
        );

        // WooCommerce does not support payment method token display based on live or test payment gateway mode.
        // For this reason, we are doing this filtering.
        // Because a customer can set only one payment method token as default,
        // So it is possible that either live or test payment method will not have a default payment method token.
        // Because of this reason, the payment method token will not be selected by default on the checkout page.
        // To fix this issue, we are setting up the first payment method token as the default payment method.
        if (! $hasDefaultPaymentMethodToken) {
            foreach ($paymentMethodTokens as $paymentMethodToken) {
                if (Constants::GATEWAY_ID === $paymentMethodToken->get_gateway_id()) {
                    $paymentMethodToken->set_default(true);
                    break;
                }
            }
        }

        if (!empty($this->excludeTokens)) {
            $paymentMethodTokens = array_filter(
                $paymentMethodTokens,
                function ($paymentMethodToken) {
                    return !in_array($paymentMethodToken->get_token(), $this->excludeTokens);
                }
            );
        }

        return $paymentMethodTokens;
    }
}
