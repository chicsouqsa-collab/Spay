<?php

/**
 * This class is responsible to provide method to create and format date.
 *
 * @package StellarPay\Core\Support\Facades\DateTime
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Support\Facades\DateTime;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

/**
 * @since 1.0.0
 */
class TemporalFacade
{
    /**
     * @since 1.0.0
     */
    public function toDateTime(string $date): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $date, wp_timezone());
    }

    /**
     * @since 1.0.0
     */
    public function toGMTDateTime(string $date): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $date, new DateTimeZone('GMT'));
    }

    /**
     * @since 1.0.0
     */
    public function getGMTDateTime(DateTimeInterface $date): DateTime
    {
        return DateTime::createFromFormat(
            'Y-m-d H:i:s',
            get_gmt_from_date(self::getFormattedDateTime($date)),
            new DateTimeZone('GMT')
        );
    }

    /**
     * @since 1.1.0
     */
    public function getGMTDateTimeWithMilliseconds(DateTimeInterface $date): DateTime
    {
        return DateTime::createFromFormat(
            'Y-m-d H:i:s.v',
            get_gmt_from_date(self::getFormattedDateTimeWithMilliseconds($date), 'Y-m-d H:i:s.v'),
            new DateTimeZone('GMT')
        );
    }

    /**
     * @since 1.1.0
     */
    public function getCurrentDateTime(): DateTime
    {
        return new DateTime('now', wp_timezone());
    }

    /**
     * @since 1.9.0
     */
    public function getCurrentGMTDateTime(): DateTime
    {
        return new DateTime('now', new DateTimeZone('UTC'));
    }

    /**
     * @since 1.0.0
     *
     * @param DateTimeInterface $dateTime
     *
     * @return string
     */
    public function getFormattedDateTime(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @since 1.1.0
     */
    public function getFormattedDateTimeWithMilliseconds(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s.v');
    }

    /**
     * @since 1.1.0
     */
    public function toDateTimeFromMilliseconds(string $date): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s.v', $date, wp_timezone());
    }

     /**
     * @since 1.1.0
     */
    public function toGMTDateTimeFromMilliseconds(string $date): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s.v', $date, new DateTimeZone('GMT'));
    }

    /**
     * @since 1.1.0
     */
    public function getWPFormattedDate(DateTimeInterface $dateTime): string
    {
        $dateFormat = get_option('date_format', 'F j, Y');

        return $dateTime->format($dateFormat);
    }

     /**
     * @since 1.1.0
     */
    public function getWPFormattedDateTime(DateTimeInterface $dateTime): string
    {
        $dateFormat = get_option('date_format', 'F j, Y');
        $timeFormat = get_option('time_format', 'g:i a');

        return $dateTime->format(sprintf('%s %s', $dateFormat, $timeFormat));
    }

    /**
     * Immutably returns a new DateTime instance with the microseconds set to 0.
     *
     * @since 1.0.0
     */
    public function withoutMicroseconds(DateTime $dateTime): DateTime
    {
        $newDateTime = clone $dateTime;

        $newDateTime->setTime(
            (int) $newDateTime->format('H'),
            (int) $newDateTime->format('i'),
            (int) $newDateTime->format('s')
        );

        return $newDateTime;
    }

    /**
     * @since 1.3.0
     */
    public function getDateTimeFromUtcTimestamp(int $timestamp): DateTime
    {
        $dateTime = new DateTime("@$timestamp");
        $dateTime->setTimezone(wp_timezone());

        return $dateTime;
    }
}
