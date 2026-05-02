<?php

declare(strict_types=1);

namespace LedgerCore\Facades;

use Illuminate\Support\Facades\Facade;

final class Ledger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ledger-core';
    }
}
