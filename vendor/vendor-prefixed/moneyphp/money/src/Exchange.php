<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Money;

use StellarPay\Vendors\Money\Exception\UnresolvableCurrencyPairException;

/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Exchange
{
    /**
     * Returns a currency pair for the passed currencies with the rate coming from a third-party source.
     *
     * @return CurrencyPair
     *
     * @throws UnresolvableCurrencyPairException When there is no currency pair (rate) available for the given currencies
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency);
}
