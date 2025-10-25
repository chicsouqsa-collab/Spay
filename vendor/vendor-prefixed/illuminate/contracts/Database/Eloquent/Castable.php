<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Illuminate\Contracts\Database\Eloquent;

interface Castable
{
    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     * @return string|\StellarPay\Vendors\Illuminate\Contracts\Database\Eloquent\CastsAttributes|\StellarPay\Vendors\Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes
     */
    public static function castUsing(array $arguments);
}
