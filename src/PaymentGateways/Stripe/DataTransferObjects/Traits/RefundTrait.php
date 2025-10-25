<?php

/**
 * RefundTrait.
 *
 * This trait is responsible for managing the refund status.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 *
 * @method string getStatus() Returns the refund status.
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\Traits;

use StellarPay\Vendors\Stripe\Refund as StripeRefund;

/**
 * RefundTrait.
 *
 * @since 1.0.0
 *
 * @method string getRefundStatus() Returns the refund status.
 */
trait RefundTrait
{
    /**
     * This function returns whether refund processed successfully.
     *
     * @since 1.0.0
     */
    public function isSuccessful(): bool
    {
        return StripeRefund::STATUS_SUCCEEDED === $this->getRefundStatus();
    }

    /**
     * This function returns whether refund failed.
     *
     * @since 1.0.0
     */
    public function isFailed(): bool
    {
        return StripeRefund::STATUS_FAILED === $this->getRefundStatus();
    }

    /**
     * This function returns whether refund is canceled.
     *
     * @since 1.0.0
     */
    public function isCanceled(): bool
    {
        return StripeRefund::STATUS_CANCELED === $this->getRefundStatus();
    }

    /**
     * This function returns whether refund is pending.
     *
     * @since 1.0.0
     */
    public function isPending(): bool
    {
        return StripeRefund::STATUS_PENDING === $this->getRefundStatus();
    }

    /**
     * This function returns whether refund requires action.
     *
     * @since 1.0.0
     */
    public function isRequiresAction(): bool
    {
        return StripeRefund::STATUS_REQUIRES_ACTION === $this->getRefundStatus();
    }
}
