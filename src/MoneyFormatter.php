<?php

namespace Pelmered\FilamentMoneyField;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\IntlLocalizedDecimalParser;
use Money\Money;
use Money\Currency;
use NumberFormatter;

class MoneyFormatter
{
    public static function format(null|int|string $value, Currency $currency, string $locale, int $outputStyle = NumberFormatter::CURRENCY): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $numberFormatter = self::getNumberFormatter($locale, $outputStyle);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        $money = new Money($value, $currency);
        return $moneyFormatter->format($money);  // outputs $1.000,00
    }

    public static function formatAsDecimal(null|int|string $value, Currency $currency, string $locale): string
    {
        return static::format($value, $currency, $locale, NumberFormatter::DECIMAL); // outputs 1.000,00
    }

    public static function parseDecimal($moneyString, Currency $currency, string $locale): string
    {
        if (is_null($moneyString) || $moneyString === '') {
            return '';
        }

        $currencies = new ISOCurrencies();
        $numberFormatter = self::getNumberFormatter($locale, NumberFormatter::DECIMAL);
        $moneyParser = new IntlLocalizedDecimalParser($numberFormatter, $currencies);

        // Needed to fix some parsing issues with small numbers such as "2,00" with "," left as thousands separator in the wrong place
        // See: https://github.com/pelmered/filament-money-field/issues/20
        $formattingRules = self::getFormattingRules($locale);
        $moneyString = str_replace($formattingRules->groupingSeparator, '', $moneyString);

        return $moneyParser->parse($moneyString, $currency)->getAmount();
    }

    public static function getFormattingRules($locale): MoneyFormattingRules
    {
        $config = config('filament-money-field');
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return new MoneyFormattingRules(
            currencySymbol: $numberFormatter->getSymbol($config['intl_currency_symbol'] ? NumberFormatter::INTL_CURRENCY_SYMBOL : NumberFormatter::CURRENCY_SYMBOL),
            fractionDigits: $numberFormatter->getAttribute(NumberFormatter::FRACTION_DIGITS),
            decimalSeparator: $numberFormatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
            groupingSeparator: $numberFormatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
        );
    }

    public static function decimalToMoneyString($moneyString, $locale): string
    {
        return str_replace(',', '.', (string)$moneyString);
    }

    private static function getNumberFormatter($locale, int $style): NumberFormatter
    {
        $numberFormatter = new NumberFormatter($locale, $style);
        $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $numberFormatter;
    }
}
