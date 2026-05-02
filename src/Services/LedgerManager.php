<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use LedgerCore\Data\CreateAccountData;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Models\LedgerAccount;
use LedgerCore\Models\LedgerEntity;

final class LedgerManager
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly JournalPostingService $posting,
        private readonly BalanceService $balances,
        private readonly ReversalService $reversals,
        private readonly LedgerReportService $reports,
    ) {
    }

    public function createEntity(
        string $name,
        ?string $code = null,
        ?string $type = null,
        ?string $baseCurrency = null,
        int|string|null $parentId = null,
        array $metadata = [],
    ): LedgerEntity {
        /** @var class-string<LedgerEntity> $model */
        $model = config('ledger.models.entity', LedgerEntity::class);

        return $model::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'base_currency' => $baseCurrency,
            'metadata' => $metadata,
            'is_active' => true,
        ]);
    }

    public function createAccount(CreateAccountData $data): LedgerAccount
    {
        return $this->accounts->create($data);
    }

    public function post(JournalEntryData $data): JournalEntry
    {
        return $this->posting->post($data);
    }

    public function postOpeningBalance(JournalEntryData $data): JournalEntry
    {
        return $this->post(new JournalEntryData(
            ledgerEntityId: $data->ledgerEntityId,
            idempotencyKey: str_starts_with($data->idempotencyKey, 'opening-balance:')
                ? $data->idempotencyKey
                : 'opening-balance:' . $data->idempotencyKey,
            referenceType: $data->referenceType ?? 'opening_balance',
            referenceId: $data->referenceId,
            description: $data->description ?? 'Opening balance',
            lines: $data->lines,
            metadata: array_merge(['kind' => 'opening_balance'], $data->metadata),
            postedAt: $data->postedAt,
            createdBy: $data->createdBy,
        ));
    }

    public function reverse(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        return $this->reversals->reverse($entry, $reason);
    }

    public function getBalance(LedgerAccount|int|string $account): string
    {
        return $this->balances->getBalance($account);
    }

    public function getStatement(int|string $accountId, mixed $from = null, mixed $to = null)
    {
        return $this->reports->accountStatement($accountId, $from, $to);
    }

    public function getTrialBalance(int|string $entityId, mixed $from = null, mixed $to = null)
    {
        return $this->reports->trialBalance($entityId, $from, $to);
    }
}
