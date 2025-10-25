<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace StellarPay\Vendors\StellarWP\Validation\Contracts;

interface ValidatesOnFrontEnd
{
    /**
     * Serializes the rule option for use on the front-end.
     *
     * @since 1.0.0
     *
     * @return int|float|string|bool|array|null
     */
    public function serializeOption();
}
