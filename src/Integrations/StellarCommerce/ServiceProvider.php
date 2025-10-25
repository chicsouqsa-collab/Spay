<?php

/**
 * ServiceProvider
 *
 * This file is used to register bootstrap the StellarCommerce integration.
 *
 * @category Integrations
 * @package  StellarPay\Integrations\StellarCommerce
 */

declare(strict_types=1);

namespace StellarPay\Integrations\StellarCommerce;

use function StellarPay\Core\container;

/**
 * Class ServiceProvider
 *
 * @since 1.0.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function register(): void
    {
        container()->singleton(Client::class);
    }

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    public function boot(): void
    {
    }
}
