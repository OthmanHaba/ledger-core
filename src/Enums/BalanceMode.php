<?php

declare(strict_types=1);

namespace LedgerCore\Enums;

enum BalanceMode: string
{
    case STRICT = 'strict';
    case ALLOW_NEGATIVE = 'allow_negative';
}
