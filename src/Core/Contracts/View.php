<?php

/**
 * This class is a contract for view.
 *
 * @package StellarPay\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

/**
 * @since 1.0.0
 */
abstract class View
{
    /**
     * @since 1.0.0
     */
    public function render(): void
    {
        echo $this->getHTML(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * @since 1.0.0
     * @return string
     */
    abstract public function getHTML(): string;
}
