<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LedgerCore\Contracts\PostingValidatorContract;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Enums\LineDirection;
use LedgerCore\Events\JournalEntryPosted;
use LedgerCore\Events\JournalEntryPosting;
use LedgerCore\Exceptions\AccountNotPostableException;
use LedgerCore\Exceptions\DuplicateJournalEntryException;
use LedgerCore\Exceptions\IdempotencyConflictException;
use LedgerCore\Exceptions\UnbalancedJournalEntryException;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Models\JournalLine;
use LedgerCore\Models\LedgerAccount;
use LedgerCore\Support\Decimal;

final class JournalPostingService
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly BalanceService $balances,
        private readonly PostingValidatorContract $validator,
    ) {
    }

    public function post(JournalEntryData $data): JournalEntry
    {
        return DB::transaction(function () use ($data): JournalEntry {
            /** @var class-string<JournalEntry> $entryModel */
            $entryModel = config('ledger.models.journal_entry', JournalEntry::class);

            $payloadHash = $data->payloadHash();
            $existing = $entryModel::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing !== null) {
                if ($existing->payload_hash === $payloadHash && config('ledger.posting.return_existing_on_duplicate_idempotency_key', true)) {
                    return $existing->load('lines');
                }

                throw new IdempotencyConflictException('A journal entry already exists for this idempotency key with a different payload.');
            }

            event(new JournalEntryPosting($data));
            $this->validator->validate($data);
            $this->validateBalanced($data);

            /** @var Collection<int, LedgerAccount> $affectedAccounts */
            $affectedAccounts = $this->loadAndValidateAccounts($data);

            $entry = $entryModel::query()->create([
                'ledger_entity_id' => $data->ledgerEntityId,
                'idempotency_key' => $data->idempotencyKey,
                'payload_hash' => $payloadHash,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId === null ? null : (string) $data->referenceId,
                'description' => $data->description,
                'status' => JournalEntryStatus::POSTED,
                'posted_at' => $data->postedAt ?? now(),
                'created_by' => $data->createdBy === null ? null : (string) $data->createdBy,
                'metadata' => $data->metadata,
            ]);

            /** @var class-string<JournalLine> $lineModel */
            $lineModel = config('ledger.models.journal_line', JournalLine::class);

            foreach ($data->lines as $line) {
                $account = $affectedAccounts->get((string) $line->accountId);

                $lineModel::query()->create([
                    'journal_entry_id' => $entry->getKey(),
                    'ledger_account_id' => $account->getKey(),
                    'direction' => $line->direction()->value,
                    'amount' => $line->amount(),
                    'currency' => $line->currency ?? $account->currency,
                    'base_amount' => $line->baseAmount(),
                    'exchange_rate' => $line->exchangeRate,
                    'memo' => $line->memo,
                    'metadata' => $line->metadata,
                ]);

                $this->balances->applyPostedLine($account, $line, (int) $entry->getKey());
            }

            $entry->load(['lines.account', 'entity']);
            event(new JournalEntryPosted($entry));

            return $entry;
        });
    }

    /**
     * @return Collection<string, LedgerAccount>
     */
    private function loadAndValidateAccounts(JournalEntryData $data): Collection
    {
        if (count($data->lines) < 2) {
            throw new UnbalancedJournalEntryException('A journal entry must contain at least two lines.');
        }

        /** @var class-string<LedgerAccount> $accountModel */
        $accountModel = config('ledger.models.account', LedgerAccount::class);
        $ids = collect($data->lines)->map(fn (JournalLineData $line): string => (string) $line->accountId)->unique()->values();

        $query = $accountModel::query()->whereKey($ids->all());

        if (config('ledger.posting.lock_accounts_during_posting', true)) {
            $query->lockForUpdate();
        }

        /** @var Collection<string, LedgerAccount> $accounts */
        $accounts = $query->get()->keyBy(fn (LedgerAccount $account): string => (string) $account->getKey());

        if ($accounts->count() !== $ids->count()) {
            throw new AccountNotPostableException('One or more accounts could not be resolved.');
        }

        foreach ($data->lines as $line) {
            $account = $accounts->get((string) $line->accountId);
            $this->accounts->ensurePostable($account);
            $this->accounts->ensureCurrencyCompatible($account, $line->currency);

            if (! config('ledger.posting.allow_cross_entity_entries', false) && (string) $account->ledger_entity_id !== (string) $data->ledgerEntityId) {
                throw new AccountNotPostableException('All accounts must belong to the journal entry ledger entity.');
            }
        }

        return $accounts;
    }

    private function validateBalanced(JournalEntryData $data): void
    {
        $currencies = collect($data->lines)->map(fn (JournalLineData $line): ?string => $line->currency)->filter()->unique();
        $isMultiCurrency = $currencies->count() > 1;
        $hasBaseAmounts = collect($data->lines)->contains(fn (JournalLineData $line): bool => $line->baseAmount !== null);

        if ($isMultiCurrency && config('ledger.currency.require_base_amount_for_multi_currency', true)) {
            foreach ($data->lines as $line) {
                if ($line->baseAmount === null) {
                    throw new UnbalancedJournalEntryException('Multi-currency journal entries require base amounts for all lines.');
                }
            }
        }

        if ($isMultiCurrency || $hasBaseAmounts) {
            $this->assertDirectionTotalsBalance($data->lines, true);

            return;
        }

        $this->assertDirectionTotalsBalance($data->lines, false);
    }

    /**
     * @param array<int, JournalLineData> $lines
     */
    private function assertDirectionTotalsBalance(array $lines, bool $useBaseAmount): void
    {
        $debits = Decimal::zero();
        $credits = Decimal::zero();

        foreach ($lines as $line) {
            $amount = $useBaseAmount ? $line->baseAmount() : $line->amount();

            if ($amount === null) {
                throw new UnbalancedJournalEntryException('Base amount is required for this journal entry.');
            }

            if ($line->direction() === LineDirection::DEBIT) {
                $debits = Decimal::add($debits, $amount);
            } else {
                $credits = Decimal::add($credits, $amount);
            }
        }

        if (Decimal::compare($debits, $credits) !== 0) {
            throw new UnbalancedJournalEntryException("Debits [{$debits}] do not equal credits [{$credits}].");
        }
    }
}
