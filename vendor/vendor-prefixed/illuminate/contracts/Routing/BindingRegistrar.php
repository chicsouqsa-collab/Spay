<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Illuminate\Contracts\Routing;

interface BindingRegistrar
{
    /**
     * Add a new route parameter binder.
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder);

    /**
     * Get the binding callback for a given binding.
     *
     * @param  string  $key
     * @return \Closure
     */
    public function getBindingCallback($key);
}
