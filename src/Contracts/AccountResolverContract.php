<?php

declare(strict_types=1);

namespace LedgerCore\Contracts;

use LedgerCore\Models\LedgerAccount;

interface AccountResolverContract
{
    public function resolve(int|string|LedgerAccount $account): LedgerAccount;
}
