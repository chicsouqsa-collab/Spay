<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace StellarPay\Vendors\StellarWP\Validation\Rules;

use Closure;
use StellarPay\Vendors\StellarWP\Validation\Commands\ExcludeValue;
use StellarPay\Vendors\StellarWP\Validation\Rules\Abstracts\ConditionalRule;

/**
 * Exclude a field unless the given conditions are met.
 *
 * @see Exclude
 *
 * @since 1.2.0
 */
class ExcludeUnless extends ConditionalRule
{
    /**
     * @inheritDoc
     *
     * @since 1.2.0
     */
    public static function id(): string
    {
        return 'excludeUnless';
    }

    /**
     * @inheritDoc
     *
     * @since 1.2.0
     */
    public function __invoke($value, Closure $fail, string $key, array $values)
    {
        if ($this->conditions->fails($values)) {
            return new ExcludeValue();
        }
    }
}
