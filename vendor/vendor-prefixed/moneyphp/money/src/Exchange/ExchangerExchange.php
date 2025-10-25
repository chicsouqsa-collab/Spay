<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Money\Exchange;

use Exchanger\Contract\ExchangeRateProvider;
use Exchanger\CurrencyPair as ExchangerCurrencyPair;
use Exchanger\Exception\Exception as ExchangerException;
use Exchanger\ExchangeRateQuery;
use StellarPay\Vendors\Money\Currency;
use StellarPay\Vendors\Money\CurrencyPair;
use StellarPay\Vendors\Money\Exception\UnresolvableCurrencyPairException;
use StellarPay\Vendors\Money\Exchange;

/**
 * Provides a way to get exchange rate from a third-party source and return a currency pair.
 *
 * @author Maksim (Ellrion) Platonov <ellrion11@gmail.com>
 */
final class ExchangerExchange implements Exchange
{
    /**
     * @var ExchangeRateProvider
     */
    private $exchanger;

    public function __construct(ExchangeRateProvider $exchanger)
    {
        $this->exchanger = $exchanger;
    }

    /**
     * {@inheritdoc}
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency)
    {
        try {
            $query = new ExchangeRateQuery(
                new ExchangerCurrencyPair($baseCurrency->getCode(), $counterCurrency->getCode())
            );
            $rate = $this->exchanger->getExchangeRate($query);
        } catch (ExchangerException $e) {
            throw UnresolvableCurrencyPairException::createFromCurrencies($baseCurrency, $counterCurrency);
        }

        return new CurrencyPair($baseCurrency, $counterCurrency, $rate->getValue());
    }
}
