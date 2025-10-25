<?php

/**
 * This class is responsible to provide enum for a subscription period.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;
use StellarPay\Core\ValueObjects\Traits\HasDefaultValue;

/**
 * @since 1.3.0 Rename function and argument name.
 * @since 1.0.0
 *
 * @property string $value
 * @method static SubscriptionPeriod DAY()
 * @method static SubscriptionPeriod WEEK()
 * @method static SubscriptionPeriod MONTH()
 * @method static SubscriptionPeriod YEAR()
 * @method bool isDAY()
 * @method bool isWEEK()
 * @method bool isMONTH()
 * @method bool isYEAR()
 */
class SubscriptionPeriod extends Enum
{
    use HasDefaultValue;

    /**
     * @since 1.0.0
     */
    public const DAY = 'day';

    /**
     * @since 1.0.0
     */
    public const WEEK = 'week';

    /**
     * @since 1.0.0
     */
    public const MONTH = 'month';

    /**
     * @since 1.0.0
     */
    public const YEAR = 'year';

    /**
     * @since 1.0.0
     */
    public function getLabelByFrequency(int $frequencyCount = 1): string
    {
        switch ($this->value) {
            case self::DAY:
                return _n('day', 'days', $frequencyCount, 'stellarpay');
            case self::WEEK:
                return _n('week', 'weeks', $frequencyCount, 'stellarpay');
            case self::MONTH:
                return _n('month', 'months', $frequencyCount, 'stellarpay');
            case self::YEAR:
                return _n('year', 'years', $frequencyCount, 'stellarpay');
            default:
                return '';
        }
    }

    /**
     * Get the daily, weekly, monthly, yearly label.
     *
     * @since 1.5.0
     */
    public function getAdverbLabelByFrequency(int $frequencyCount = 1): string
    {
        if (1 < $frequencyCount) {
            return sprintf(
                /* translators: Example: every 2 days. 1: Frequency count. 2: Day/Week/Month/Year. */
                esc_html__('every %1$d %2$s', 'stellarpay'),
                $frequencyCount,
                $this->getLabelByFrequency($frequencyCount)
            );
        }

        switch ($this->value) {
            case self::DAY:
                return esc_html__('daily', 'stellarpay');
            case self::WEEK:
                return esc_html__('weekly', 'stellarpay');
            case self::MONTH:
                return esc_html__('monthly', 'stellarpay');
            case self::YEAR:
                return esc_html__('yearly', 'stellarpay');
            default:
                return '';
        }
    }

    /**
     * Get an alternative label for the subscription period.
     * I.e. a day, a week, a month, a year, every 2 days, every 2 weeks, every 2 months, every 2 years.
     *
     * @since 1.6.0
     */
    public function getFormattedAdverbLabelByFrequency(int $frequencyCount = 1): string
    {
        if (1 < $frequencyCount) {
            return sprintf(
                /* translators: Example: every 2 days. 1: Frequency count. 2: Day/Week/Month/Year. */
                esc_html__('every %1$d %2$s', 'stellarpay'),
                $frequencyCount,
                $this->getLabelByFrequency($frequencyCount)
            );
        }

        switch ($this->value) {
            case self::DAY:
                return esc_html__('a day', 'stellarpay');
            case self::WEEK:
                return esc_html__('a week', 'stellarpay');
            case self::MONTH:
                return esc_html__('a month', 'stellarpay');
            case self::YEAR:
                return esc_html__('a year', 'stellarpay');
            default:
                return '';
        }
    }

    /**
     * Get the labels for the subscription period.
     *
     * @since 1.8.0
     * @return array<string, string>
     */
    public static function selectFieldOptions(): array
    {
        return [
            'day'   => esc_html__('Daily', 'stellarpay'),
            'week'  => esc_html__('Weekly', 'stellarpay'),
            'month' => esc_html__('Monthly', 'stellarpay'),
            'year'  => esc_html__('Yearly', 'stellarpay'),
        ];
    }

    /**
     * @since 1.8.0
     */
    public static function defaultValue(): self
    {
        return self::MONTH();
    }
}
