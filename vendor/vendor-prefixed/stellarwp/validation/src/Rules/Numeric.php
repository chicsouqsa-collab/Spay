<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace StellarPay\Vendors\StellarWP\Validation\Rules;

use Closure;
use StellarPay\Vendors\StellarWP\Validation\Contracts\ValidatesOnFrontEnd;
use StellarPay\Vendors\StellarWP\Validation\Contracts\ValidationRule;

class Numeric implements ValidationRule, ValidatesOnFrontEnd
{
    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public static function id(): string
    {
        return 'numeric';
    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public static function fromString(string $options = null): ValidationRule
    {
        return new self();
    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public function serializeOption()
    {
        return null;
    }

    /**
     * @inheritDoc
     *
     * @since 1.0.0
     */
    public function __invoke($value, Closure $fail, string $key, array $values)
    {
        if (!is_numeric($value)) {
            $fail(sprintf(__('%s must be numeric', '%TEXTDOMAIN%'), '{field}'));
        }
    }
}
