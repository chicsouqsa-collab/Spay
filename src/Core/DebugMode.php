<?php

/**
 * This class used to flag whether app is running in debug mode.
 * Primarily, it uses WP_DEBUG constant declared by WordPress to set debug mode status.
 */

declare(strict_types=1);

namespace StellarPay\Core;

/**
 * @since 1.0.0
 */
class DebugMode
{
    /**
     * @since 1.0.0
     */
    protected bool $isEnabled;

    /**
     * @since 1.0.0
     */
    public function __construct(bool $enabled)
    {
        $this->isEnabled = $enabled;
    }

    /**
     * @since 1.0.0
     * @return self
     */
    public static function makeWithWpDebugConstant(): self
    {
        return new self(WP_DEBUG);
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}
