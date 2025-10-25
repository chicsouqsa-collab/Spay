<?php

/**
 * This class is responsible to provide enum for `subscription cancel at`.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Support\Enum;
use StellarPay\Core\ValueObjects\Traits\HasLabels;

/**
 * @since 1.3.0
 *
 * @method static SubscriptionCancelAt IMMEDIATELY()
 * @method static SubscriptionCancelAt END_OF_THE_CURRENT_PERIOD()
 * @method bool isImmediately()
 * @method bool isEndOfTheCurrentPeriod()
 */
class SubscriptionCancelAt extends Enum
{
    use HasLabels;

    /**
     * @since 1.3.0
     */
    public const IMMEDIATELY = 'immediately';

    /**
     * @since 1.3.0
     */
    public const END_OF_THE_CURRENT_PERIOD = 'endOfTheCurrentPeriod';

    /**
     * @inheritdoc
     * @since 1.3.0
     */
    public static function labels(): array
    {
        return [
            self::IMMEDIATELY => esc_html__('Cancel Immediately', 'stellarpay'),
            self::END_OF_THE_CURRENT_PERIOD => esc_html__('End of the current period', 'stellarpay'),
        ];
    }

    /**
     * @since 1.3.0
     * @throws Exception
     */
    public static function getOptions(): array
    {
        return [
            [
                'key' => self::IMMEDIATELY,
                'label' => self::from(self::IMMEDIATELY)->label(),
            ],
            [
                'key' => self::END_OF_THE_CURRENT_PERIOD,
                'label' => self::from(self::END_OF_THE_CURRENT_PERIOD)->label(),
            ]
        ];
    }
}
