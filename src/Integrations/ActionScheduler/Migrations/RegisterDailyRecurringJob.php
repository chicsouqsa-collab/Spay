<?php

/**
 * @package StellarPay\Integrations\ActionScheduler\Migrations
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler\Migrations;

use StellarPay\Core\Migrations\Contracts\Migration;
use StellarPay\Integrations\ActionScheduler\Jobs\DailyRecurringJob;

/**
 * @since 1.3.0
 */
class RegisterDailyRecurringJob extends Migration
{
    /**
     * @since 1.3.0
     */
    public function run()
    {
        $dailyRecurringJob = new DailyRecurringJob();
        $dailyRecurringJob();
    }

    /**
     * @since 1.3.0
     */
    public static function id(): string
    {
        return 'register-daily-recurring-job';
    }

    /**
     * @since 1.3.0
     * @return int
     */
    public static function timestamp(): int
    {
        return strtotime('2025-01-16 20:39:00');
    }

    /**
     * @since 1.3.0
     */
    public static function title(): string
    {
        return esc_html__('Register Daily Recurring Job For Action Scheduler', 'stellarpay');
    }
}
