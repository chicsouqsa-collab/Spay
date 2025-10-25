<?php

/**
 * Admin Page Validator
 *
 * This trait is responsible for validating if the current page is one of our plugin's pages.
 *
 * @package StellarPay\AdminDashboard\Traits
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Traits;

/**
 * Trait AdminPageValidator
 *
 * @since 1.0.0
 */
trait AdminPageValidator
{
    /**
     * Check if the current page is one of our plugin's pages.
     *
     * @since 1.0.0
     */
    public function isTopLevelPluginPage(): bool
    {
        $screen = get_current_screen();

        return strpos($screen->base, 'toplevel_page_stellarpay') !== false
            && current_user_can('manage_options');
    }
}
