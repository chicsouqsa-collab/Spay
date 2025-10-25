<?php

/**
 * This class is responsible to handle fee recovery.
 *
 * @package StellarPay\Integrations\WooCommerce\Cart
 * @since 1.6.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Cart;

use StellarPay\AdminDashboard\Repositories\OptionsRepository;

/**
 * @since 1.6.0
 */
class FeeRecovery
{
    /**
     * @since 1.6.0
     */
    protected OptionsRepository $optionsRepository;

    /**
     * @since 1.6.0
     */
    public function __construct(OptionsRepository $optionsRepository)
    {
        $this->optionsRepository = $optionsRepository;
    }

    /**
     * @since 1.6.0
     */
    public function __invoke(): void
    {
        if (!$this->optionsRepository->isFeeRecoveryEnabled()) {
            return;
        }

        /**
         * Using the global cart it's important especially for
         * WooSubscriptions integration. Otherwise, the fee will
         * be added to the recurring cart too.
         *
         * @see https://woocommerce.com/document/subscriptions/develop/recurring-cart-fees/#section-3
         */
        $cart = WC()->cart;
        $feeAmount = $this->getFeeAmount((float) $cart->get_cart_contents_total());

        if ($feeAmount <= 0) {
            return;
        }

        $lineItemDescriptor = $this->optionsRepository->getLineItemDescriptorFeeRecovery();

        $fee = [
            'name' => esc_html($lineItemDescriptor),
            'amount' => $feeAmount,
        ];

        $cart->fees_api()->add_fee($fee);
    }

    /**
     * @since 1.6.0
     */
    protected function getFeeAmount(float $totalAmount): float
    {
        $defaultFeeAmount = 0.0;

        if ($totalAmount < 0) {
            return $defaultFeeAmount;
        }

        $percentageFeeRecovery = $this->optionsRepository->getPercentageFeeRecovery() / 100;
        $flatAmountFeeRecovery = $this->optionsRepository->getFlatAmountFeeRecovery();

        $feeAmount = ( ($totalAmount + $flatAmountFeeRecovery) / (1 - $percentageFeeRecovery) ) - $totalAmount;

        if ($feeAmount <= 0) {
            return $defaultFeeAmount;
        }

        return round($feeAmount, 2);
    }
}
