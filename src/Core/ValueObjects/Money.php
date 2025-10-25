<?php

/**
 * Money value object.
 *
 * This class is responsible for managing money value object.
 *
 * @package StellarPay/Core/ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Vendors\Money\Currencies;
use StellarPay\Vendors\Money\Currency;

/**
 * Class Money
 *
 * @since 1.0.0
 */
class Money
{
    /**
     * Amount
     *
     * @since 1.0.0
     */
    protected float $amount;

    /**
     * Currency code
     *
     * Currency code should be in ISO 4217 format.
     *
     * @since 1.0.0
     */
    protected Currency $currency;

    /**
     * Decimal position
     *
     * @since 1.0.0
     */
    protected int $decimalPosition;

    /**
     * Money constructor.
     *
     * @since 1.0.0
     *
     * @param float  $amount   Amount in decimals.
     * @param string $currency Currency.
     */
    public function __construct(float $amount, string $currency)
    {
        $this->currency = new Currency(strtoupper($currency));
        $this->amount = $amount;
        $this->decimalPosition = (new Currencies\ISOCurrencies())->subunitFor($this->currency);
    }

    /**
     * Make a new Money instance.
     *
     * @since 1.0.0
     *
     * @return static
     */
    public static function make(float $amount, string $currency): Money
    {
        return new static($amount, $currency); // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     *
     * @return static
     */
    public static function fromMinorAmount(int $minorAmount, string $currencyCode)
    {
        $currencyCode = strtoupper($currencyCode);
        $currency = new Currency($currencyCode);
        $isoCurrencies = new Currencies\ISOCurrencies();

        $amount = in_array(strtolower($currencyCode), self::zeroDecimalCurrencies(), true)
            ? $minorAmount
            : $minorAmount / ( 10 ** $isoCurrencies->subunitFor($currency) );

        return new static($amount, $currencyCode); // @phpstan-ignore-line
    }

    /**
     * @since 1.0.0
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get a minor amount.
     *
     * @since 1.0.0
     */
    public function getMinorAmount(): int
    {
        $amount = in_array(strtolower($this->getCurrencyCode()), self::zeroDecimalCurrencies(), true)
            ? $this->amount
            : number_format(
                $this->amount * (10 ** $this->decimalPosition),
                $this->decimalPosition,
                '.',
                ''
            );

        return absint($amount);
    }

    /**
     * Get currency.
     *
     * @since 1.0.0
     */
    public function getCurrencyCode(): string
    {
        return $this->currency->getCode();
    }

    /**
     * List of currencies supported by Stripe that has no decimals
     * https://stripe.com/docs/currencies#zero-decimal
     *
     * @since 1.0.0
     *
     * @return array $currencies List of zero decimal currencies.
     */
    public static function zeroDecimalCurrencies(): array
    {
        return [
            'bif', // Burundian Franc
            'clp', // Chilean Peso
            'djf', // Djiboutian Franc
            'gnf', // Guinean Franc
            'jpy', // Japanese Yen
            'kmf', // Comorian Franc
            'krw', // South Korean Won
            'mga', // Malagasy Ariary
            'pyg', // Paraguayan Guaraní
            'rwf', // Rwandan Franc
            'ugx', // Ugandan Shilling
            'vnd', // Vietnamese Đồng
            'vuv', // Vanuatu Vatu
            'xaf', // Central African Cfa Franc
            'xof', // West African Cfa Franc
            'xpf', // Cfp Franc
        ];
    }
}
