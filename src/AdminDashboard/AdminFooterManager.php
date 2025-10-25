<?php

/**
 * Admin Footer Manager
 *
 * This file is responsible for managing admin footer.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard;

/**
 * Class AdminFooterManager
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */
class AdminFooterManager
{
    /**
     * Remove the default WordPress footer text.
     *
     * @since 1.0.0
     */
    public function __invoke(): string
    {
        return '';
    }
}
