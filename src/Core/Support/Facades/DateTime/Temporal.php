<?php

/**
 * This class is responsible to provide access to "TemporalFacade" class as facade.
 *
 * @package StellarPay\Core\Support\Facades\DateTime
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Support\Facades\DateTime;

use DateTime;
use DateTimeInterface;
use StellarPay\Core\Support\Facades\Facade;

/**
 * @since 1.0.0
 *
 * @method static DateTime toDateTime(string $date)
 * @method static DateTime toGMTDateTime(string $date)
 * @method static DateTime getGMTDateTime(DateTimeInterface $date)
 * @method static DateTime getCurrentDateTime()
 * @method static DateTime getCurrentGMTDateTime()
 * @method static string getFormattedDateTime(DateTimeInterface $dateTime)
 * @method static string getWPFormattedDateTime(DateTimeInterface $dateTime)
 * @method static string getWPFormattedDate(DateTimeInterface $dateTime)
 * @method static DateTime withoutMicroseconds(DateTime $dateTime)
 * @method static string getFormattedDateTimeWithMilliseconds(DateTimeInterface $dateTime)
 * @method static DateTime getGMTDateTimeWithMilliseconds(DateTimeInterface $dateTime)
 * @method static DateTime toDateTimeFromMilliseconds(string $dateTime)
 * @method static DateTime toGMTDateTimeFromMilliseconds(string $dateTime)
 * @method static DateTime getDateTimeFromUtcTimestamp(int $timestamp)
 */
class Temporal extends Facade
{
    /**
     * @since 1.1.0
     */
    protected function getFacadeAccessor(): string
    {
        return TemporalFacade::class;
    }
}
