<?php

/**
 * This class represents the payment gateway mode.
 *
 *  Payment gateway has the following modes:
 *  1. Test
 *  2. Live
 *
 * This value object standardizes this across app.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use InvalidArgumentException;
use StellarPay\Core\Support\Enum;

/**
 * Class Mode
 *
 * @since 1.0.0
 */
class PaymentGatewayMode extends Enum
{
    /**
     * @since 1.0.0
     */
    public const TEST = 'test';

    /**
     * @since 1.0.0
     */
    public const LIVE = 'live';

    /**
     * @since 1.0.0
     */
    private string $mode;

    /**
     * @since 1.0.0
     */
    public function __construct(string $mode)
    {
        $this->validate($mode);

        $this->mode = $mode;
        parent::__construct($mode);
    }

    /**
     * @since 1.0.0
     */
    private function validate(string $mode): void
    {
        if (self::TEST !== $mode && self::LIVE !== $mode) {
            throw new InvalidArgumentException('Invalid payment gateway mode');
        }
    }

    /**
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->mode;
    }

    /**
     * @since 1.0.0
     */
    public function isLive(): bool
    {
        return self::LIVE === $this->mode;
    }

    /**
     * @since 1.0.0
     */
    public function isTest(): bool
    {
        return self::TEST === $this->mode;
    }

    /**
     * @since 1.0.0
     */
    public static function test(): self
    {
        return new self(self::TEST);
    }

    /**
     * @since 1.0.0
     */
    public static function live(): self
    {
        return new self(self::LIVE);
    }

    /**
     * @since 1.0.0
     */
    public function __toString(): string
    {
        return $this->mode;
    }

    /**
     * @since 1.0.0
     */
    public function match(PaymentGatewayMode $paymentGatewayMode): bool
    {
        return $this->getId() === $paymentGatewayMode->getId();
    }

    /**
     * @since 1.0.0
     */
    public function notMatch(PaymentGatewayMode $paymentGatewayMode): bool
    {
        return ! $this->match($paymentGatewayMode);
    }
}
