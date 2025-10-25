<?php

/**
 * This trait is responsible to provide logic to prevent payment method duplication on the Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Traits;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use WC_Order;

/**
 * Trait FindMatchForPaymentMethod
 *
 * @since 1.0.0
 * @property OrderRepository $orderRepository
 * @property PaymentMethodService $paymentMethodService
 */
trait FindMatchForPaymentMethod
{
    /**
     * Attach a payment method to a Stripe customer.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function findMatchForPaymentMethodWithOrder(WC_Order $order, PaymentMethodDTO $newPaymentMethod): ?PaymentMethodDTO
    {
        $stripeCustomerId = $this->orderRepository->getCustomerId($order);

        return $this-> findMatchForPaymentMethodWithStripeCustomerId($stripeCustomerId, $newPaymentMethod);
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function findMatchForPaymentMethodWithStripeCustomerId(string $stripeCustomerId, PaymentMethodDTO $newPaymentMethod): ?PaymentMethodDTO
    {
        $allPaymentMethods = $this->paymentMethodService->getAllPaymentMethods($stripeCustomerId);
        $filteredAllPaymentMethods = $allPaymentMethods->filter(function ($paymentMethod) use ($newPaymentMethod) {
            // Stripe generates fingerprint a few payment method types.
            // We can use this fingerprint to detect duplicate cards.
            // Read more: https://support.stripe.com/questions/how-can-i-detect-duplicate-cards-or-bank-accounts
            $whitelistedPaymentMethodTypes = ['card', 'us_bank_account', 'sepa_debit'];

            // If the payment method is not allowlisted, skip it.
            // We don't want to validate duplicate payment methods for non-allowlisted payment methods.
            if (! in_array($paymentMethod->getType(), $whitelistedPaymentMethodTypes, true)) {
                return false;
            }

            // If the payment method is a different type, skip it.
            if (! $paymentMethod->hasSameType($newPaymentMethod)) {
                return false;
            }

            // If the payment method does not have the same fingerprint, skip it.
            if (! $paymentMethod->hasSameFingerprint($newPaymentMethod)) {
                return false;
            }

            // If the expiration date is different, it is a different card.
            if ($paymentMethod->isCard() && ! $newPaymentMethod->hasEqualExpiryDate($paymentMethod)) {
                return false;
            }

            return true;
        });

        return $filteredAllPaymentMethods->first();
    }
}
