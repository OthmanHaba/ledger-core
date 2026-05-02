<?php

declare(strict_types=1);

namespace LedgerCore\Enums;

enum LineDirection: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function opposite(): self
    {
        return $this === self::DEBIT ? self::CREDIT : self::DEBIT;
    }
}
