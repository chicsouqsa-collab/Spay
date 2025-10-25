<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace StellarPay\Vendors\StellarWP\Validation;

use StellarPay\Vendors\StellarWP\Validation\Rules\Boolean;
use StellarPay\Vendors\StellarWP\Validation\Rules\Currency;
use StellarPay\Vendors\StellarWP\Validation\Rules\DateTime;
use StellarPay\Vendors\StellarWP\Validation\Rules\Email;
use StellarPay\Vendors\StellarWP\Validation\Rules\Exclude;
use StellarPay\Vendors\StellarWP\Validation\Rules\ExcludeIf;
use StellarPay\Vendors\StellarWP\Validation\Rules\ExcludeUnless;
use StellarPay\Vendors\StellarWP\Validation\Rules\In;
use StellarPay\Vendors\StellarWP\Validation\Rules\InStrict;
use StellarPay\Vendors\StellarWP\Validation\Rules\Integer;
use StellarPay\Vendors\StellarWP\Validation\Rules\Max;
use StellarPay\Vendors\StellarWP\Validation\Rules\Min;
use StellarPay\Vendors\StellarWP\Validation\Rules\Nullable;
use StellarPay\Vendors\StellarWP\Validation\Rules\NullableIf;
use StellarPay\Vendors\StellarWP\Validation\Rules\NullableUnless;
use StellarPay\Vendors\StellarWP\Validation\Rules\Numeric;
use StellarPay\Vendors\StellarWP\Validation\Rules\Optional;
use StellarPay\Vendors\StellarWP\Validation\Rules\OptionalIf;
use StellarPay\Vendors\StellarWP\Validation\Rules\OptionalUnless;
use StellarPay\Vendors\StellarWP\Validation\Rules\Required;
use StellarPay\Vendors\StellarWP\Validation\Rules\Size;

class ServiceProvider
{
    private $validationRules = [
        Required::class,
        Min::class,
        Max::class,
        Size::class,
        Numeric::class,
        In::class,
        InStrict::class,
        Integer::class,
        Email::class,
        Currency::class,
        Exclude::class,
        ExcludeIf::class,
        ExcludeUnless::class,
        Nullable::class,
        NullableIf::class,
        NullableUnless::class,
        Optional::class,
        OptionalIf::class,
        OptionalUnless::class,
        DateTime::class,
        Boolean::class,
    ];

    /**
     * Registers the validation rules registrar with the container
     */
    public function register()
    {
        Config::getServiceContainer()->singleton(ValidationRulesRegistrar::class, function () {
            $register = new ValidationRulesRegistrar();

            foreach ($this->validationRules as $rule) {
                $register->register($rule);
            }

            do_action(Config::getHookPrefix() . 'register_validation_rules', $register);

            return $register;
        });
    }
}
