<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LedgerCore\Enums\NormalBalance;
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

        $accountsQuery = $accountModel::query()
            ->where('ledger_entity_id', $entityId)
            ->orderBy('code')
            ->orderBy('name');

        if ($from === null && $to === null) {
            return $accountsQuery
                ->with('balance')
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

        $totals = $this->aggregateLineTotalsByAccount($entityId, $from, $to);

        return $accountsQuery->get()->map(function (LedgerAccount $account) use ($totals): array {
            $debit = $totals[(string) $account->getKey()]['debit'] ?? Decimal::zero();
            $credit = $totals[(string) $account->getKey()]['credit'] ?? Decimal::zero();
            $balance = $account->normal_balance === NormalBalance::DEBIT
                ? Decimal::sub($debit, $credit)
                : Decimal::sub($credit, $debit);

            return [
                'account_id' => $account->getKey(),
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'currency' => $account->currency,
                'debit_total' => Decimal::normalize($debit),
                'credit_total' => Decimal::normalize($credit),
                'balance' => Decimal::normalize($balance),
            ];
        });
    }

    /**
     * @return array<string, array{debit: string, credit: string}>
     */
    private function aggregateLineTotalsByAccount(int|string $entityId, mixed $from, mixed $to): array
    {
        $entriesTable = config('ledger.tables.journal_entries', 'journal_entries');
        $linesTable = config('ledger.tables.journal_lines', 'journal_lines');

        $rows = DB::table($linesTable . ' as l')
            ->join($entriesTable . ' as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.ledger_entity_id', $entityId)
            ->whereNotNull('e.posted_at')
            ->when($from, fn ($query, $value) => $query->where('e.posted_at', '>=', $value))
            ->when($to, fn ($query, $value) => $query->where('e.posted_at', '<=', $value))
            ->groupBy('l.ledger_account_id', 'l.direction')
            ->selectRaw('l.ledger_account_id, l.direction, SUM(l.amount) as total')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $accountId = (string) $row->ledger_account_id;
            $totals[$accountId] ??= ['debit' => Decimal::zero(), 'credit' => Decimal::zero()];
            $totals[$accountId][(string) $row->direction] = Decimal::normalize((string) $row->total);
        }

        return $totals;
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
