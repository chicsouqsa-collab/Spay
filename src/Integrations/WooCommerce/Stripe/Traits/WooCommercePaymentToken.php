<?php

/**
 * This trait uses to handle logic around the WooCommerce payment token
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Traits;

use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;
use WP_User;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * Trait WooCommercePaymentToken
 *
 * @since 1.7.0 Rename trait
 * @since 1.0.0
 */
trait WooCommercePaymentToken
{
    /**
     * Save card type payment method to customer on website.
     *
     * @since 1.0.0
     */
    protected function saveCardTypePaymentMethod(PaymentMethodDTO $paymentMethod, int $customerId): bool
    {
        $token = new WC_Payment_Token_CC();

        $token->set_gateway_id(Constants::GATEWAY_ID);
        $token->set_token($paymentMethod->getId());
        $token->set_card_type($paymentMethod->getCardBrand());
        $token->set_last4($paymentMethod->getCardLast4());
        $token->set_expiry_month((string) $paymentMethod->getCardExpMonth());
        $token->set_expiry_year((string)$paymentMethod->getCardExpYear());

        $token->set_user_id($customerId);

        $token->add_meta_data(
            dbMetaKeyGenerator('payment_method_mode', true),
            $paymentMethod->getPaymentGatewayMode()->getId(),
            true
        );

        return (bool)absint($token->save());
    }

    /**
     * @since 1.0.0
     */
    protected function isDuplicatePaymentMethodToken(WP_User $user, PaymentMethodDTO $paymentMethodDTO): bool
    {
        // If the payment method is already saved, then return failure.
        $tokens = WC_Payment_Tokens::get_customer_tokens($user->ID, Constants::GATEWAY_ID);
        if (! empty($tokens)) {
            foreach ($tokens as $token) {
                if ($token->get_token() === $paymentMethodDTO->getId()) {
                    return true;
                }
            }
        }

        return  false;
    }
}
