<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Money;

/**
 * Formats Money objects.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
interface MoneyFormatter
{
    /**
     * Formats a Money object as string.
     *
     * @return string
     *
     * Exception\FormatterException
     */
    public function format(Money $money);
}
