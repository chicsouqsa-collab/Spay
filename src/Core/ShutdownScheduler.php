<?php

/**
 * This class uses to register shutdown jobs.
 *
 * @link https://www.php.net/manual/en/function.register-shutdown-function.php
 * @package StellarPay\Webhook
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use StellarPay\Core\Exceptions\BindingResolutionException;

/**
 * @since 1.1.0
 */
class ShutdownScheduler
{
    /**
     * @since 1.1.0
     * @var callable[]
     */
    private array $callbacks = [];

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function callRegisteredShutdown()
    {
        foreach ($this->callbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * @since 1.1.0
     */
    public function registerShutdownJob(callable $callback): void
    {
        $this->callbacks[] = $callback;
    }
}
