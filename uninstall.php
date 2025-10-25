<?php

/**
 * This file responsible to clean plugin data if admin opted-in.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

// phpcs:disable
// If this file is called directly, abort.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

if (empty(get_option('_stellarpay_delete_all_data_on_delete'))) {
    return;
}

global $wpdb;

// Remove all options added by the plugin.
$like = '%stellarpay%';
$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $like)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Drop tables
$pluginTableNames = [
    $wpdb->prefix . 'stellarpay_subscriptions_meta',
    $wpdb->prefix . 'stellarpay_subscriptions',
    $wpdb->prefix . 'stellarpay_webhook_events',
    $wpdb->prefix . 'stellarpay_migration_logs',
];

foreach ($pluginTableNames as $tableName) {
    $wpdb->query("DROP TABLE IF EXISTS $tableName"); // phpcs:ignore
}

// Remove cron jobs.
wp_clear_scheduled_hook('stellarpay_opt_in_stripe_account_email');

// Cancel action scheduler jobs
$actionSchedulerTableName = $wpdb->prefix . 'actionscheduler_actions';
$wpdb->query(
    $wpdb->prepare(
        "UPDATE $actionSchedulerTableName
        SET status = %s
        WHERE hook LIKE %s
        AND status = %s",
        'canceled',       // New status
        'stellarpay_%',   // Hook prefix
        'pending'         // Current status
    )
);
// phpcs:enable
