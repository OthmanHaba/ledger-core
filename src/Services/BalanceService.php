<?php

declare(strict_types=1);

namespace LedgerCore\Services;

use LedgerCore\Contracts\AccountResolverContract;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Enums\LineDirection;
use LedgerCore\Enums\NormalBalance;
use LedgerCore\Exceptions\InsufficientBalanceException;
use LedgerCore\Models\AccountBalance;
use LedgerCore\Models\LedgerAccount;
use LedgerCore\Support\Decimal;

final class BalanceService
{
    public function __construct(private readonly AccountResolverContract $resolver)
    {
    }

    public function initializeBalance(LedgerAccount $account): AccountBalance
    {
        /** @var class-string<AccountBalance> $model */
        $model = config('ledger.models.account_balance', AccountBalance::class);

        return $model::query()->firstOrCreate(
            ['ledger_account_id' => $account->getKey()],
            [
                'debit_total' => Decimal::zero(),
                'credit_total' => Decimal::zero(),
                'balance' => Decimal::zero(),
                'currency' => $account->currency,
            ],
        );
    }

    public function applyLine(JournalLineData $line): AccountBalance
    {
        return $this->applyPostedLine(
            $this->resolver->resolve($line->accountId),
            $line,
            null,
        );
    }

    public function applyPostedLine(LedgerAccount $account, JournalLineData $line, ?int $journalEntryId): AccountBalance
    {
        $balance = $this->initializeBalance($account);

        if (config('ledger.posting.lock_accounts_during_posting', true)) {
            $balance = $balance->newQuery()
                ->whereKey($balance->getKey())
                ->lockForUpdate()
                ->firstOrFail();
        }

        $debitTotal = (string) $balance->debit_total;
        $creditTotal = (string) $balance->credit_total;

        if ($line->direction() === LineDirection::DEBIT) {
            $debitTotal = Decimal::add($debitTotal, $line->amount());
        } else {
            $creditTotal = Decimal::add($creditTotal, $line->amount());
        }

        $newBalance = $account->normal_balance === NormalBalance::DEBIT
            ? Decimal::sub($debitTotal, $creditTotal)
            : Decimal::sub($creditTotal, $debitTotal);

        $shouldPreventNegative = config('ledger.posting.prevent_negative_balances_by_default', true) && ! $account->allow_negative;

        if ($shouldPreventNegative && Decimal::isNegative($newBalance)) {
            throw new InsufficientBalanceException("Posting would make account [{$account->getKey()}] negative.");
        }

        $balance->forceFill([
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'balance' => $newBalance,
            'currency' => $account->currency ?? $line->currency,
            'last_journal_entry_id' => $journalEntryId,
        ])->save();

        return $balance;
    }

    public function getBalance(int|string|LedgerAccount $account): string
    {
        $account = $this->resolver->resolve($account);
        $balance = $account->balance ?: $this->initializeBalance($account);

        return Decimal::normalize((string) $balance->balance);
    }

    public function assertSufficientBalance(LedgerAccount $account, string $amount): void
    {
        if ($account->allow_negative) {
            return;
        }

        if (Decimal::compare($this->getBalance($account), $amount) < 0) {
            throw new InsufficientBalanceException("Account [{$account->getKey()}] has insufficient balance.");
        }
    }
}
