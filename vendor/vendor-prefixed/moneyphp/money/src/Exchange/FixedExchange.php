<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Money\Exchange;

use StellarPay\Vendors\Money\Currency;
use StellarPay\Vendors\Money\CurrencyPair;
use StellarPay\Vendors\Money\Exception\UnresolvableCurrencyPairException;
use StellarPay\Vendors\Money\Exchange;

/**
 * Provides a way to get exchange rate from a static list (array).
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
final class FixedExchange implements Exchange
{
    /**
     * @var array
     */
    private $list;

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        if (isset($this->list[$baseCurrency->getCode()][$counterCurrency->getCode()])) {
            return new CurrencyPair(
                $baseCurrency,
                $counterCurrency,
                $this->list[$baseCurrency->getCode()][$counterCurrency->getCode()]
            );
        }

        throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
    }
}
