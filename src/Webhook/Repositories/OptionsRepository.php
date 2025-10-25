<?php

/**
 * This class is responsible for managing Webhook Event related options.
 *
 * @package StellarPay\Webhook\Repositories
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\Repositories;

use StellarPay\Webhook\Models\WebhookEvent;

/**
 * OptionsRepository class.
 *
 * @since 1.1.0
 */
class OptionsRepository
{
    /**
     * @since 1.1.0
     */
    public function setWebhookEventTableVersion(string $version): bool
    {
        return update_option(WebhookEvent::getTableName() . '_table_version', $version, false);
    }
}
