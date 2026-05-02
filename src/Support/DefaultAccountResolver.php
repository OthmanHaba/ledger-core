<?php

declare(strict_types=1);

namespace LedgerCore\Support;

use LedgerCore\Contracts\AccountResolverContract;
use LedgerCore\Models\LedgerAccount;

final class DefaultAccountResolver implements AccountResolverContract
{
    public function resolve(int|string|LedgerAccount $account): LedgerAccount
    {
        if ($account instanceof LedgerAccount) {
            return $account;
        }

        /** @var class-string<LedgerAccount> $model */
        $model = config('ledger.models.account', LedgerAccount::class);

        return $model::query()->whereKey($account)->firstOrFail();
    }
}
