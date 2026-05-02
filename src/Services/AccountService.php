<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use LedgerCore\Contracts\AccountResolverContract;
use LedgerCore\Data\CreateAccountData;
use LedgerCore\Exceptions\AccountCurrencyMismatchException;
use LedgerCore\Exceptions\AccountNotPostableException;
use LedgerCore\Models\LedgerAccount;

final class AccountService
{
    public function __construct(
        private readonly AccountResolverContract $resolver,
        private readonly BalanceService $balances,
    ) {
    }

    public function create(CreateAccountData $data): LedgerAccount
    {
        /** @var class-string<LedgerAccount> $model */
        $model = config('ledger.models.account', LedgerAccount::class);

        $account = $model::query()->create([
            'ledger_entity_id' => $data->ledgerEntityId,
            'parent_id' => $data->parentId,
            'code' => $data->code,
            'name' => $data->name,
            'type' => $data->type()->value,
            'normal_balance' => $data->normalBalance()->value,
            'currency' => $data->currency,
            'counterparty_type' => $data->counterpartyType,
            'counterparty_id' => $data->counterpartyId === null ? null : (string) $data->counterpartyId,
            'is_control_account' => $data->isControlAccount,
            'is_postable' => $data->isPostable,
            'allow_negative' => $data->allowNegative,
            'metadata' => $data->metadata,
            'is_active' => true,
        ]);

        $this->balances->initializeBalance($account);

        return $account->refresh();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int|string|LedgerAccount $id): LedgerAccount
    {
        return $this->resolver->resolve($id);
    }

    public function ensurePostable(LedgerAccount $account): void
    {
        if (! $account->is_active || ! $account->is_postable) {
            throw new AccountNotPostableException("Account [{$account->getKey()}] is not active and postable.");
        }
    }

    public function ensureCurrencyCompatible(LedgerAccount $account, ?string $currency): void
    {
        if ($account->currency !== null && $currency !== null && $account->currency !== $currency) {
            throw new AccountCurrencyMismatchException("Account [{$account->getKey()}] requires {$account->currency}, got {$currency}.");
        }
    }
}
