<?php

/**
 * This class is responsible to provide enum for `subscription refund`.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.4.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;
use StellarPay\Core\ValueObjects\Traits\HasLabels;
use StellarPay\Core\ValueObjects\Traits\HasOptions;

/**
 * @since 1.4.0
 *
 * @method static RefundType NO_REFUND()
 * @method static RefundType LAST_PAYMENT()
 * @method static RefundType PRORATED_AMOUNT()
 * @method bool isNoRefund()
 * @method bool isLastPayment()
 * @method bool isProratedAmount()
 */
class RefundType extends Enum
{
    use HasLabels;
    use HasOptions;

    /**
     * @since 1.4.0
     */
    public const NO_REFUND = 'noRefund';

    /**
     * @since 1.4.0
     */
    public const LAST_PAYMENT = 'lastPayment';

    /**
     * @since 1.4.0
     */
    public const PRORATED_AMOUNT = 'proratedAmount';

    /**
     * @inheritdoc
     * @since 1.4.0
     */
    public static function labels(): array
    {
        return [
            self::NO_REFUND => esc_html__('No refund', 'stellarpay'),
            // translators: %s - subscription amount.
            self::LAST_PAYMENT => esc_html__('Last payment %s', 'stellarpay'),
            // translators: %1$s - prorated amount, %2$s - subscription amount.
            self::PRORATED_AMOUNT => esc_html__('Prorated amount %1$s of %2$s', 'stellarpay'),
        ];
    }
}
