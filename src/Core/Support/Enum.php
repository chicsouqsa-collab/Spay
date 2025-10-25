<?php

/**
 * This class uses as contract to create enum.
 *
 * @package StellarPay\Core\Support
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Support;

use BadMethodCallException;

/**
 * @since 1.0.0
 */
class Enum extends \StellarPay\Vendors\MyCLabs\Enum\Enum
{
    /**
     * Adds support for is{CONSTANT_NAME} methods.
     *  - So if an Enum has an ACTIVE value, then an isActive()
     *  - So if an Enum has an ACTIVE_STATE value, then an isActiveState()
     * instance methods are automatically available.
     *
     * @since 1.0.0
     */
    public function __call($name, $arguments): bool
    {
        if (strpos($name, 'is') === 0) {
            $constant = self::getConstant(Str::after($name, 'is'));

            if (null === $constant) {
                throw new BadMethodCallException(esc_html("$name does not match a corresponding enum constant."));
            }

            return $this->equals(parent::$constant());
        }

        throw new BadMethodCallException(esc_html("Method $name does not exist on enum"));
    }

    /**
     * @since 1.0.0
     */
    protected static function hasConstant(string $name): bool
    {
        $constantName = self::getConstant($name);

        return null !== $constantName;
    }

    /**
     * @since 1.0.0
     */
    protected static function getConstant(string $name): ?string
    {
        $constants = static::keys();

        $filteredConstants = array_filter($constants, function ($constant) use ($name) {
            $existing = Str::lower($constant);
            $expected = Str::lower($name);

            if (Str::contains($existing, '_')) {
                $existing = Str::replace('_', '', $existing);
            }

            return $expected === $existing;
        });


        return ! empty($filteredConstants) ? current($filteredConstants) : null;
    }
}
