<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LedgerCore\Models\AccountBalance;
use LedgerCore\Models\JournalLine;
use LedgerCore\Models\LedgerAccount;
use LedgerCore\Support\Decimal;

final class LedgerReportService
{
    public function trialBalance(int|string $entityId, mixed $from = null, mixed $to = null): Collection
    {
        /** @var class-string<LedgerAccount> $accountModel */
        $accountModel = config('ledger.models.account', LedgerAccount::class);

        return $accountModel::query()
            ->where('ledger_entity_id', $entityId)
            ->with('balance')
            ->orderBy('code')
            ->orderBy('name')
            ->get()
            ->map(fn (LedgerAccount $account): array => [
                'account_id' => $account->getKey(),
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'currency' => $account->currency,
                'debit_total' => Decimal::normalize((string) ($account->balance?->debit_total ?? '0')),
                'credit_total' => Decimal::normalize((string) ($account->balance?->credit_total ?? '0')),
                'balance' => Decimal::normalize((string) ($account->balance?->balance ?? '0')),
            ]);
    }

    public function generalLedger(int|string $entityId, array $filters = []): LengthAwarePaginator
    {
        /** @var class-string<JournalLine> $lineModel */
        $lineModel = config('ledger.models.journal_line', JournalLine::class);

        return $lineModel::query()
            ->with(['entry', 'account'])
            ->whereHas('entry', function (Builder $query) use ($entityId, $filters): void {
                $query->where('ledger_entity_id', $entityId);
                $this->applyEntryFilters($query, $filters);
            })
            ->when($filters['account_id'] ?? null, fn (Builder $query, mixed $accountId) => $query->where('ledger_account_id', $accountId))
            ->orderBy(
                DB::raw('(select posted_at from ' . config('ledger.tables.journal_entries', 'journal_entries') . ' where id = journal_entry_id)'),
            )
            ->paginate($filters['per_page'] ?? 50);
    }

    public function accountStatement(int|string $accountId, mixed $from = null, mixed $to = null): Collection
    {
        /** @var class-string<JournalLine> $lineModel */
        $lineModel = config('ledger.models.journal_line', JournalLine::class);

        return $lineModel::query()
            ->with('entry')
            ->where('ledger_account_id', $accountId)
            ->whereHas('entry', function (Builder $query) use ($from, $to): void {
                $query
                    ->when($from, fn (Builder $query) => $query->where('posted_at', '>=', $from))
                    ->when($to, fn (Builder $query) => $query->where('posted_at', '<=', $to));
            })
            ->get()
            ->sortBy(fn (JournalLine $line) => $line->entry?->posted_at)
            ->values();
    }

    public function accountBalances(int|string $entityId, array $filters = []): Collection
    {
        /** @var class-string<AccountBalance> $balanceModel */
        $balanceModel = config('ledger.models.account_balance', AccountBalance::class);

        return $balanceModel::query()
            ->with('account.entity')
            ->whereHas('account', fn (Builder $query) => $query->where('ledger_entity_id', $entityId))
            ->when($filters['currency'] ?? null, fn (Builder $query, string $currency) => $query->where('currency', $currency))
            ->get();
    }

    private function applyEntryFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['from'] ?? null, fn (Builder $query, mixed $from) => $query->where('posted_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, mixed $to) => $query->where('posted_at', '<=', $to))
            ->when($filters['reference_type'] ?? null, fn (Builder $query, string $type) => $query->where('reference_type', $type))
            ->when($filters['reference_id'] ?? null, fn (Builder $query, string $id) => $query->where('reference_id', $id));
    }
}
