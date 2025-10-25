<?php

/**
 * This class is responsible to provide enum for payment intent status.
 *
 * @package StellarPay\Stripe\ValueObjects
 * @since 1.4.1
 */

declare(strict_types=1);

namespace StellarPay\Stripe\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.4.1
 *
 * @method static PaymentIntentStatus CANCELED()
 * @method static PaymentIntentStatus PROCESSING()
 * @method static PaymentIntentStatus REQUIRES_ACTION()
 * @method static PaymentIntentStatus REQUIRES_CAPTURE()
 * @method static PaymentIntentStatus REQUIRES_CONFIRMATION()
 * @method static PaymentIntentStatus REQUIRES_PAYMENT_METHOD()
 * @method static PaymentIntentStatus SUCCEEDED()
 * @method bool isCanceled()
 * @method bool isProcessing()
 * @method bool isRequiresAction()
 * @method bool isRequiresCapture()
 * @method bool isRequiresConfirmation()
 * @method bool isRequiresPaymentMethod()
 * @method bool isSucceeded()
 */
class PaymentIntentStatus extends Enum
{
    public const CANCELED = 'canceled';
    public const PROCESSING = 'processing';
    public const REQUIRES_ACTION = 'requires_action';
    public const REQUIRES_CAPTURE = 'requires_capture';
    public const REQUIRES_CONFIRMATION = 'requires_confirmation';
    public const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    public const SUCCEEDED = 'succeeded';

    /**
     * @since 1.7.0 Makes the function non-static.
     * @since 1.4.1
     */
    public function isPaidStatus(): bool
    {
        $paidStatuses = [self::PROCESSING, self::SUCCEEDED];

        return in_array($this->getValue(), $paidStatuses, true);
    }
}
