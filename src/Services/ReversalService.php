<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use Illuminate\Support\Facades\DB;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Events\JournalEntryReversed;
use LedgerCore\Exceptions\InvalidReversalException;
use LedgerCore\Models\JournalEntry;

final class ReversalService
{
    public function __construct(private readonly JournalPostingService $posting)
    {
    }

    public function reverse(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $reason): JournalEntry {
            $entry = $entry->newQuery()
                ->with('lines')
                ->whereKey($entry->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status !== JournalEntryStatus::POSTED) {
                throw new InvalidReversalException('Only posted journal entries can be reversed.');
            }

            $reversal = $this->posting->post(new JournalEntryData(
                ledgerEntityId: $entry->ledger_entity_id,
                idempotencyKey: 'reversal:' . $entry->uuid,
                referenceType: 'journal_entry_reversal',
                referenceId: (string) $entry->getKey(),
                description: $reason ?? 'Reversal for journal entry ' . $entry->uuid,
                lines: $entry->lines->map(fn ($line): JournalLineData => new JournalLineData(
                    accountId: $line->ledger_account_id,
                    direction: $line->direction->opposite(),
                    amount: (string) $line->amount,
                    currency: $line->currency,
                    baseAmount: $line->base_amount === null ? null : (string) $line->base_amount,
                    exchangeRate: $line->exchange_rate === null ? null : (string) $line->exchange_rate,
                    memo: 'Reversal: ' . ($line->memo ?? ''),
                    metadata: ['reverses_line_id' => $line->getKey()],
                ))->all(),
                metadata: [
                    'reverses_entry_id' => $entry->getKey(),
                    'reverses_entry_uuid' => $entry->uuid,
                    'reason' => $reason,
                ],
            ));

            $entry->forceFill([
                'status' => JournalEntryStatus::REVERSED,
                'reversed_at' => now(),
                'reversed_by_entry_id' => $reversal->getKey(),
            ])->save();

            event(new JournalEntryReversed($entry->refresh(), $reversal));

            return $reversal;
        });
    }
}
