<?php

declare(strict_types=1);

namespace LedgerCore\Contracts;

interface CurrencyConverterContract
{
    public function convert(string $amount, string $fromCurrency, string $toCurrency, ?string $rate = null): string;
}
