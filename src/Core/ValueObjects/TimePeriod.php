<?php

/**
 * This file is responsible for managing time periods.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.6.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use DateInterval;
use DateTime;
use StellarPay\Core\Exceptions\Primitives\RuntimeException;
use StellarPay\Core\Support\Enum;
use StellarPay\Core\Support\Facades\DateTime\Temporal;

/**
 * @since 1.6.0
 */
class TimePeriod extends Enum
{
    /**
     * @since 1.6.0
     */
    public const ONE_MONTH = '1-month';

    /**
     * @since 1.6.0
     */
    public const TWO_MONTH = '2-months';

    /**
     * @since 1.6.0
     */
    public const THREE_MONTH = '3-months';

    /**
     * @since 1.6.0
     */
    public const SIX_MONTH = '6-months';

    /**
     * @since 1.6.0
     */
    public const ONE_YEAR = '1-year';

    /**
     * @since 1.6.0
     *
     * @return DateTime
     * @throws RuntimeException
     */
    public function getDateTime(): DateTime
    {
        try {
            $timePeriod = str_replace('-', ' ', $this->getValue());
            $date = Temporal::getCurrentDateTime();

            $dateInterval = DateInterval::createFromDateString("+ $timePeriod");
            $date->sub($dateInterval);
        } catch (\Exception $e) {
            throw new RuntimeException(esc_html('Invalid date operation: ' . $e->getMessage()));
        }

        return $date;
    }
}
