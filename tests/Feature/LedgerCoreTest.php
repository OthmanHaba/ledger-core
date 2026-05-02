<?php

declare(strict_types=1);

use LedgerCore\Data\CreateAccountData;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;
use LedgerCore\Exceptions\AccountCurrencyMismatchException;
use LedgerCore\Exceptions\AccountNotPostableException;
use LedgerCore\Exceptions\IdempotencyConflictException;
use LedgerCore\Exceptions\LedgerException;
use LedgerCore\Exceptions\UnbalancedJournalEntryException;
use LedgerCore\Models\JournalLine;
use LedgerCore\Services\LedgerManager;
use LedgerCore\Services\LedgerReportService;

function ledger(): LedgerManager
{
    return app(LedgerManager::class);
}

function entity(): mixed
{
    return ledger()->createEntity('Test Book', 'BOOK', baseCurrency: 'USD');
}

function account(int|string $entityId, string $name, AccountType $type, NormalBalance $normal, ?string $currency = 'USD', bool $postable = true, bool $allowNegative = false): mixed
{
    return ledger()->createAccount(new CreateAccountData(
        ledgerEntityId: $entityId,
        name: $name,
        type: $type,
        normalBalance: $normal,
        code: strtoupper(substr($name, 0, 3)) . random_int(100, 999),
        currency: $currency,
        isControlAccount: ! $postable,
        isPostable: $postable,
        allowNegative: $allowNegative,
    ));
}

function standardAccounts(int|string $entityId): array
{
    return [
        account($entityId, 'Cash', AccountType::ASSET, NormalBalance::DEBIT),
        account($entityId, 'Opening Equity', AccountType::EQUITY, NormalBalance::CREDIT),
    ];
}

it('can create ledger entity', function (): void {
    $entity = entity();

    expect($entity->exists)->toBeTrue()
        ->and($entity->uuid)->not->toBeEmpty()
        ->and($entity->base_currency)->toBe('USD');
});

it('can create account', function (): void {
    $entity = entity();
    $account = account($entity->id, 'Cash', AccountType::ASSET, NormalBalance::DEBIT);

    expect($account->exists)->toBeTrue()
        ->and($account->balance)->not->toBeNull()
        ->and($account->type)->toBe(AccountType::ASSET);
});

it('can post balanced journal entry', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    $entry = ledger()->post(new JournalEntryData(
        ledgerEntityId: $entity->id,
        idempotencyKey: 'balanced-1',
        lines: [
            JournalLineData::debit($cash->id, '100.00000000', 'USD'),
            JournalLineData::credit($equity->id, '100.00000000', 'USD'),
        ],
    ));

    expect($entry->lines)->toHaveCount(2)
        ->and($entry->status->value)->toBe('posted');
});

it('cannot post unbalanced journal entry', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    ledger()->post(new JournalEntryData($entity->id, 'unbalanced-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '99.00000000', 'USD'),
    ]));
})->throws(UnbalancedJournalEntryException::class);

it('cannot post with inactive account', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $cash->forceFill(['is_active' => false])->save();

    ledger()->post(new JournalEntryData($entity->id, 'inactive-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));
})->throws(AccountNotPostableException::class);

it('cannot post with non-postable control account', function (): void {
    $entity = entity();
    $cash = account($entity->id, 'Cash', AccountType::ASSET, NormalBalance::DEBIT);
    $control = account($entity->id, 'Control', AccountType::LIABILITY, NormalBalance::CREDIT, postable: false);

    ledger()->post(new JournalEntryData($entity->id, 'control-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($control->id, '100.00000000', 'USD'),
    ]));
})->throws(AccountNotPostableException::class);

it('cannot post with currency mismatch', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    ledger()->post(new JournalEntryData($entity->id, 'currency-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'LYD'),
        JournalLineData::credit($equity->id, '100.00000000', 'LYD'),
    ]));
})->throws(AccountCurrencyMismatchException::class);

it('updates balances correctly for debit-normal accounts', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    ledger()->post(new JournalEntryData($entity->id, 'debit-normal-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    expect(ledger()->getBalance($cash))->toBe('100.00000000');
});

it('updates balances correctly for credit-normal accounts', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    ledger()->post(new JournalEntryData($entity->id, 'credit-normal-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    expect(ledger()->getBalance($equity))->toBe('100.00000000');
});

it('idempotent post returns existing entry for same key and same payload', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $data = new JournalEntryData($entity->id, 'same-key-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]);

    $first = ledger()->post($data);
    $second = ledger()->post($data);

    expect($second->id)->toBe($first->id);
});

it('idempotent post with same key and different payload throws exception', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    ledger()->post(new JournalEntryData($entity->id, 'same-key-different-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    ledger()->post(new JournalEntryData($entity->id, 'same-key-different-1', [
        JournalLineData::debit($cash->id, '200.00000000', 'USD'),
        JournalLineData::credit($equity->id, '200.00000000', 'USD'),
    ]));
})->throws(IdempotencyConflictException::class);

it('can reverse posted entry', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $entry = ledger()->post(new JournalEntryData($entity->id, 'reverse-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $reversal = ledger()->reverse($entry, 'Mistake');

    expect($reversal->exists)->toBeTrue()
        ->and($entry->refresh()->status->value)->toBe('reversed');
});

it('reversal creates opposite lines', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $entry = ledger()->post(new JournalEntryData($entity->id, 'reverse-lines-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $reversal = ledger()->reverse($entry)->load('lines');

    expect($reversal->lines->pluck('direction.value')->all())->toContain('credit', 'debit');
});

it('reversal updates balances correctly', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $entry = ledger()->post(new JournalEntryData($entity->id, 'reverse-balances-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    ledger()->reverse($entry);

    expect(ledger()->getBalance($cash))->toBe('0.00000000')
        ->and(ledger()->getBalance($equity))->toBe('0.00000000');
});

it('cannot edit posted journal lines', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $entry = ledger()->post(new JournalEntryData($entity->id, 'line-edit-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $line = $entry->lines()->firstOrFail();
    $line->forceFill(['memo' => 'changed'])->save();
})->throws(LedgerException::class);

it('cannot delete posted journal lines', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    $entry = ledger()->post(new JournalEntryData($entity->id, 'line-delete-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $entry->lines()->firstOrFail()->delete();
})->throws(LedgerException::class);

it('cannot change account currency after posting', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    ledger()->post(new JournalEntryData($entity->id, 'account-currency-change-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $cash->forceFill(['currency' => 'LYD'])->save();
})->throws(LedgerException::class);

it('can generate trial balance', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    ledger()->post(new JournalEntryData($entity->id, 'trial-balance-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $rows = app(LedgerReportService::class)->trialBalance($entity->id);

    expect($rows)->toHaveCount(2);
});

it('can generate account statement', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);
    ledger()->post(new JournalEntryData($entity->id, 'statement-1', [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100.00000000', 'USD'),
    ]));

    $statement = app(LedgerReportService::class)->accountStatement($cash->id);

    expect($statement)->toHaveCount(1)
        ->and($statement->first())->toBeInstanceOf(JournalLine::class);
});

it('opening balance posts through normal journal mechanism', function (): void {
    $entity = entity();
    [$cash, $equity] = standardAccounts($entity->id);

    $entry = ledger()->postOpeningBalance(new JournalEntryData($entity->id, 'cash-2026', [
        JournalLineData::debit($cash->id, '100000.00000000', 'USD'),
        JournalLineData::credit($equity->id, '100000.00000000', 'USD'),
    ]));

    expect($entry->idempotency_key)->toBe('opening-balance:cash-2026')
        ->and($entry->lines)->toHaveCount(2)
        ->and(ledger()->getBalance($cash))->toBe('100000.00000000');
});
