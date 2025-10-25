<?php

/**
 * @package StellarPay\Webhook
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Migrations\MigrationsRegister;
use StellarPay\Webhook\Migrations\CreateWebhookEventsTable;

use function StellarPay\Core\container;

/**
 * @since 1.2.0.
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.2.0
     */
    public function register(): void
    {
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        container(MigrationsRegister::class)->addMigration(CreateWebhookEventsTable::class);
    }
}
