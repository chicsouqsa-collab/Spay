<?php

/**
 * This class uses to add custom class to the "body" tag in admin.
 *
 * @package StellarPay\AdminDashboard\Actions
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use StellarPay\Core\Constants;

/**
 * @since 1.3.0
 */
class AddClassesToAdminBody
{
    /**
     * @since 1.3.0
     */
    public function __invoke(string $classes): string
    {
        $currentWpVersion = (float) get_bloginfo('version');

        $newClasses = [
            // This class use to provide backward compatibility to an older WordPress version.
            // For example, WP modal component width issue before WP 6.5
            $currentWpVersion < 6.5 ? Constants::slugPrefixed('-wp-lt-65') : ''
        ];

        $newClassesString = implode(' ', array_filter($newClasses));

        return $newClassesString ? "$classes $newClassesString" : $classes;
    }
}
