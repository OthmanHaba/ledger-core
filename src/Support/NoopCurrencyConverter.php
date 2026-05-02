<?php

declare(strict_types=1);

namespace LedgerCore\Support;

use LedgerCore\Contracts\CurrencyConverterContract;

final class NoopCurrencyConverter implements CurrencyConverterContract
{
    public function convert(string $amount, string $fromCurrency, string $toCurrency, ?string $rate = null): string
    {
        if ($fromCurrency === $toCurrency) {
            return Decimal::normalize($amount);
        }

        if ($rate === null) {
            throw new \InvalidArgumentException('A conversion rate is required for different currencies.');
        }

        return bcmul($amount, $rate, Decimal::scale());
    }
}
