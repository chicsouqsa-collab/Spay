<?php

/**
 * This class uses to translate stripe error code to user-friendly error messages.
 *
 * Error List -
 * https://docs.stripe.com/error-codes#amount-too-large
 *
 * @package StellarPay\Integrations\Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;

/**
 * @since 1.0.0
 */
class StripeErrorMessage
{
    /**
     * @since 1.0.0
     */
    public static function getErrorMessage(StripeAPIException $exception): string
    {
        $stripeErrorCode = $exception->getStripeErrorCode();
        $stripeErrorMessageList = self::getErrorMessageList();

        if (array_key_exists($stripeErrorCode, $stripeErrorMessageList)) {
            return $stripeErrorMessageList[$stripeErrorCode];
        }

        return $exception->getMessage();
    }

    /**
     * @since 1.0.0
     */
    public static function getErrorMessageList(): array
    {
        return [
            'amount_too_large' => esc_html__(
                'The specified amount is greater than the maximum amount allowed. Use a lower amount and try again.',
                'stellarpay'
            ),
            'amount_too_small' => esc_html__(
                'The specified amount is less than the minimum amount allowed. Use a higher amount and try again.',
                'stellarpay'
            )
        ];
    }
}
