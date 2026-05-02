<?php

declare(strict_types=1);

namespace LedgerCore\Enums;

enum NormalBalance: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
