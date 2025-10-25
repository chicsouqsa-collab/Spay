<?php

/**
 * Notice Manager
 *
 * This file is responsible for managing admin notices.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard;

/**
 * Class NoticeManager
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */
class NoticeManager
{
    use Traits\AdminPageValidator;

    /**
     * Disable other plugin's notices on our plugin's admin pages.
     *
     * @since 1.0.0
     */
    public function __invoke(): void
    {
        // Check if the current page is one of your plugin's pages
        if ($this->isTopLevelPluginPage()) {
            // Remove all actions hooked to 'admin_notices'
            remove_all_actions('admin_notices');
            // Remove all actions hooked to 'all_admin_notices'
            remove_all_actions('all_admin_notices');
        }
    }

    /**
     * Show SSL required notice.
     *
     * @since 1.6.0 Use static method.
     * @since 1.0.0
     */
    public function showSslRequiredNotice(): void
    {
        ?>
        <div class="notice notice-error">
            <p><?php echo self::getSslRequiredNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        </div>
        <?php
    }

    /**
     * Get SSL required notice.
     *
     * @since 1.6.0 Make static method.
     * @since 1.0.0
     */
    public static function getSslRequiredNotice(): string
    {
        return esc_html__('StellarPay requires SSL to be enabled. Please enable SSL to continue.', 'stellarpay');
    }
}
