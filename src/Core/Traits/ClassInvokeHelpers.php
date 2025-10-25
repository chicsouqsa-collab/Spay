<?php

/**
 * This trait provides helper methods to `__invoke()`.
 *
 * @package StellarPay\Core\Traits
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Traits;

use StellarPay\Core\Exceptions\BindingResolutionException;

use function StellarPay\Core\container;

/**
 * Trait ClassInvokeHelpers
 *
 * @since 1.1.0
 */
trait ClassInvokeHelpers
{
    /**
     * Call __invoke
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    protected function invokeClass($className, ...$params): void
    {
        $invokable = container($className);

        $invokable(...$params);
    }

    /**
     * Call __invoke and return the result
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    protected function invokeClassAndReturn($className, ...$params)
    {
        $invokable = container($className);

        return $invokable(...$params);
    }
}
