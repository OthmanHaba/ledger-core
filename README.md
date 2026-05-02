# Ledger Core

`ledger-core` is a generic, reusable double-entry ledger package for Laravel applications.

It provides the accounting core that many applications need: books, accounts, journal entries, journal lines, balances, currencies, reversals, idempotency, posting validation, reporting, auditability, and optional Filament administration.

The package is intentionally domain-neutral. It does not contain models or services for transfers, customers, providers, branches, remittance, wallets, invoices, orders, subscriptions, payouts, exchanges, or any other host-application workflow. Your application owns those workflows and translates them into ledger journal entries.

## Table Of Contents

- [What This Package Solves](#what-this-package-solves)
- [Core Principles](#core-principles)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Model](#database-model)
- [Concepts](#concepts)
- [Quick Start](#quick-start)
- [Creating Ledger Entities](#creating-ledger-entities)
- [Creating Accounts](#creating-accounts)
- [Posting Journal Entries](#posting-journal-entries)
- [Opening Balances](#opening-balances)
- [Idempotency](#idempotency)
- [Reversals](#reversals)
- [Balances](#balances)
- [Reports](#reports)
- [Filament Integration](#filament-integration)
- [Host Application Use Cases](#host-application-use-cases)
- [Extending The Package](#extending-the-package)
- [Events](#events)
- [Exceptions](#exceptions)
- [Testing](#testing)
- [Operational Guidance](#operational-guidance)
- [FAQ](#faq)

## What This Package Solves

Most applications eventually need an audit-friendly financial record. A simple `balance` column is easy to start with, but it becomes difficult to reason about when you need corrections, historical reporting, idempotency, multiple accounts, multiple currencies, or audit trails.

`ledger-core` gives you a generic double-entry ledger:

- Every posting has debit and credit lines.
- Every journal entry must balance.
- Every posting is atomic.
- Every posting is idempotent.
- Posted entries are immutable.
- Corrections are made with reversals.
- Balances are cached for fast reads but derived from journal lines.
- Business workflows stay outside the package.

Use this package when your Laravel app needs accounting-style records without hardcoding your business domain into the ledger.

## Core Principles

1. The ledger core is generic.
2. Business-specific logic belongs in the host application.
3. Every posted journal entry must be balanced.
4. Posted journal entries and lines are immutable.
5. Reversal entries correct mistakes.
6. Idempotency is mandatory.
7. Cached balances are updated only by the posting service.
8. Amounts are decimal strings, not PHP floats.
9. Posting is transactional.
10. Filament support is optional.

## Requirements

- PHP 8.3 or newer
- Laravel 11 or 12
- Eloquent
- `ext-bcmath`
- A database supported by Laravel migrations
- Optional: Filament, if you want admin resources and reports

## Installation

Install the package:

```bash
composer require vendor/ledger-core
```

The package service provider is auto-discovered by Laravel through Composer.

Publish the config:

```bash
php artisan vendor:publish --tag=ledger-core-config
```

Publish the migrations:

```bash
php artisan vendor:publish --tag=ledger-core-migrations
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

The published config file is `config/ledger.php`.

```php
return [
    'tables' => [
        'entities' => 'ledger_entities',
        'accounts' => 'ledger_accounts',
        'journal_entries' => 'journal_entries',
        'journal_lines' => 'journal_lines',
        'account_balances' => 'account_balances',
    ],

    'models' => [
        'entity' => LedgerEntity::class,
        'account' => LedgerAccount::class,
        'journal_entry' => JournalEntry::class,
        'journal_line' => JournalLine::class,
        'account_balance' => AccountBalance::class,
    ],

    'currency' => [
        'base_currency' => env('LEDGER_BASE_CURRENCY', 'USD'),
        'require_base_amount_for_multi_currency' => true,
        'scale' => 8,
    ],

    'posting' => [
        'return_existing_on_duplicate_idempotency_key' => true,
        'allow_cross_entity_entries' => false,
        'prevent_negative_balances_by_default' => true,
        'lock_accounts_during_posting' => true,
        'allow_manual_entries_from_filament' => false,
        'allow_posted_metadata_updates' => false,
    ],

    'filament' => [
        'enabled' => true,
        'navigation_group' => 'Ledger',
        'navigation_icon' => 'heroicon-o-calculator',
    ],
];
```

### Important Config Options

`currency.base_currency`
: Default base currency for your installation.

`currency.require_base_amount_for_multi_currency`
: When enabled, multi-currency entries must include `base_amount` on every line.

`currency.scale`
: Decimal precision used by the package. The default is `8`.

`posting.return_existing_on_duplicate_idempotency_key`
: If enabled, reposting the same idempotency key with the same payload returns the existing entry.

`posting.allow_cross_entity_entries`
: If disabled, all accounts in one entry must belong to the same ledger entity.

`posting.prevent_negative_balances_by_default`
: If enabled, accounts cannot go negative unless the account has `allow_negative = true`.

`posting.lock_accounts_during_posting`
: If enabled, affected accounts and balances are locked during posting.

`posting.allow_manual_entries_from_filament`
: Controls whether users may create manual journal entries from Filament.

`posting.allow_posted_metadata_updates`
: Controls whether posted entry metadata can be edited.

## Database Model

### `ledger_entities`

Represents an accounting book or entity. This can map to a company, tenant, organization, store, project, department, or any other book owner in the host application. The package does not care what the entity means.

Important fields:

- `uuid`
- `parent_id`
- `name`
- `code`
- `type`
- `base_currency`
- `metadata`
- `is_active`

### `ledger_accounts`

Represents generic accounts inside a ledger entity.

Supported account types:

- `asset`
- `liability`
- `equity`
- `revenue`
- `expense`

Important fields:

- `ledger_entity_id`
- `parent_id`
- `code`
- `name`
- `type`
- `normal_balance`
- `currency`
- `counterparty_type`
- `counterparty_id`
- `is_control_account`
- `is_postable`
- `allow_negative`
- `metadata`
- `is_active`

The package does not provide a chart of accounts. Your application creates whatever accounts it needs.

### `journal_entries`

Represents the header for a posted accounting event.

Important fields:

- `ledger_entity_id`
- `idempotency_key`
- `payload_hash`
- `reference_type`
- `reference_id`
- `description`
- `status`
- `posted_at`
- `reversed_at`
- `reversed_by_entry_id`
- `metadata`

### `journal_lines`

Represents the debit and credit lines for a journal entry.

Important fields:

- `journal_entry_id`
- `ledger_account_id`
- `direction`
- `amount`
- `currency`
- `base_amount`
- `exchange_rate`
- `memo`
- `metadata`

### `account_balances`

Stores cached totals and balances for fast reads.

Important fields:

- `ledger_account_id`
- `debit_total`
- `credit_total`
- `balance`
- `currency`
- `last_journal_entry_id`

Do not update this table directly from application code. It is maintained by `JournalPostingService`.

## Concepts

### Ledger Entity

A ledger entity is the book that owns accounts and journal entries.

Examples in a host application might be:

- A company
- A tenant
- A project
- A legal entity
- A regional book
- A separate internal accounting book

The package stores the generic entity only. Your app decides what it represents.

### Account

An account is a bucket where debit and credit movements are posted.

Examples:

- Cash
- Bank
- Accounts receivable
- Accounts payable
- Clearing
- Revenue
- Expense
- Equity

These are examples only. The package does not create or enforce a chart of accounts.

### Normal Balance

The normal balance decides how the cached `balance` is calculated.

Debit-normal accounts:

```text
balance = debit_total - credit_total
```

Credit-normal accounts:

```text
balance = credit_total - debit_total
```

Typical defaults:

- Assets: debit
- Expenses: debit
- Liabilities: credit
- Equity: credit
- Revenue: credit

### Journal Entry

A journal entry is an accounting event. It contains two or more lines. Total debits must equal total credits.

### Journal Line

A journal line is one debit or credit movement against one account.

### Reversal

A reversal is a new posted journal entry with opposite lines. Reversal is how you correct posted records. Posted entries are not edited or deleted.

### Idempotency

Idempotency prevents duplicate posting when a job, webhook, command, or API request is retried.

Every journal entry requires an `idempotencyKey`.

## Quick Start

```php
use LedgerCore\Data\CreateAccountData;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;
use LedgerCore\Services\LedgerManager;

$ledger = app(LedgerManager::class);

$entity = $ledger->createEntity(
    name: 'Main Book',
    code: 'MAIN',
    baseCurrency: 'USD',
);

$cash = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Cash',
    code: '1000',
    type: AccountType::ASSET,
    normalBalance: NormalBalance::DEBIT,
    currency: 'USD',
));

$equity = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Opening Balance Equity',
    code: '3000',
    type: AccountType::EQUITY,
    normalBalance: NormalBalance::CREDIT,
    currency: 'USD',
));

$entry = $ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'opening-cash:2026',
    description: 'Opening cash balance',
    lines: [
        JournalLineData::debit($cash->id, '1000.00000000', 'USD'),
        JournalLineData::credit($equity->id, '1000.00000000', 'USD'),
    ],
));

$balance = $ledger->getBalance($cash);
```

## Creating Ledger Entities

Use `LedgerManager::createEntity()`.

```php
$entity = $ledger->createEntity(
    name: 'Main Book',
    code: 'MAIN',
    type: 'operating',
    baseCurrency: 'USD',
    parentId: null,
    metadata: [
        'external_id' => 'book_123',
    ],
);
```

The `type` field is a free string. It is intentionally not an enum because different applications organize books differently.

## Creating Accounts

Use `LedgerManager::createAccount()` or inject `AccountService`.

```php
use LedgerCore\Data\CreateAccountData;
use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;

$bank = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Bank Account',
    code: '1010',
    type: AccountType::ASSET,
    normalBalance: NormalBalance::DEBIT,
    currency: 'USD',
    parentId: null,
    isControlAccount: false,
    isPostable: true,
    allowNegative: false,
    metadata: [
        'note' => 'Primary settlement account',
    ],
));
```

### Parent Accounts And Postable Accounts

You may create account hierarchies:

```php
$assets = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Assets',
    code: '1000',
    type: AccountType::ASSET,
    normalBalance: NormalBalance::DEBIT,
    isControlAccount: true,
    isPostable: false,
));

$cash = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Cash',
    code: '1010',
    type: AccountType::ASSET,
    normalBalance: NormalBalance::DEBIT,
    parentId: $assets->id,
    isPostable: true,
));
```

Post only to postable accounts. Control accounts are useful for grouping and reporting.

### Counterparty Fields

Accounts include optional generic counterparty fields:

```php
counterpartyType: User::class,
counterpartyId: $user->id,
```

These fields are generic. They let the host application associate accounts with any model without the package knowing what that model means.

## Posting Journal Entries

Use `LedgerManager::post()` with `JournalEntryData`.

```php
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;

$entry = $ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'receipt:2026-000001',
    referenceType: 'receipt',
    referenceId: '2026-000001',
    description: 'Receipt posted by host application',
    lines: [
        JournalLineData::debit(
            accountId: $cashAccount->id,
            amount: '250.00000000',
            currency: 'USD',
            memo: 'Cash received',
        ),
        JournalLineData::credit(
            accountId: $revenueAccount->id,
            amount: '250.00000000',
            currency: 'USD',
            memo: 'Revenue recognized',
        ),
    ],
    metadata: [
        'source' => 'api',
    ],
    postedAt: now(),
));
```

### What Happens During Posting

The posting service:

1. Starts a database transaction.
2. Checks the `idempotency_key`.
3. Computes and stores a payload hash.
4. Dispatches `JournalEntryPosting`.
5. Runs custom posting validators.
6. Validates that the entry has at least two lines.
7. Validates debit and credit totals.
8. Resolves and locks affected accounts when configured.
9. Ensures accounts are active and postable.
10. Ensures account currencies are compatible.
11. Creates the journal entry.
12. Creates the journal lines.
13. Updates cached account balances.
14. Dispatches `JournalEntryPosted`.
15. Commits the transaction.

If any step fails, nothing is partially posted.

### Single-Currency Entries

For single-currency entries, total debit `amount` must equal total credit `amount`.

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'single-currency-example',
    lines: [
        JournalLineData::debit($cash->id, '100.00000000', 'USD'),
        JournalLineData::credit($revenue->id, '100.00000000', 'USD'),
    ],
));
```

### Split Entries

A single debit can be balanced by multiple credits:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'split-entry-example',
    lines: [
        JournalLineData::debit($cash->id, '1000.00000000', 'USD'),
        JournalLineData::credit($clearing->id, '980.00000000', 'USD'),
        JournalLineData::credit($feeRevenue->id, '20.00000000', 'USD'),
    ],
));
```

Multiple debits can also be balanced by one credit, or by multiple credits.

### Multi-Currency Entries

When entries contain more than one currency and `require_base_amount_for_multi_currency` is enabled, every line must include `baseAmount`.

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'multi-currency-example',
    lines: [
        JournalLineData::debit(
            accountId: $eurCash->id,
            amount: '100.00000000',
            currency: 'EUR',
            baseAmount: '108.00000000',
            exchangeRate: '1.080000000000',
        ),
        JournalLineData::credit(
            accountId: $usdClearing->id,
            amount: '108.00000000',
            currency: 'USD',
            baseAmount: '108.00000000',
            exchangeRate: '1.000000000000',
        ),
    ],
));
```

For multi-currency entries, debit `baseAmount` totals must equal credit `baseAmount` totals.

## Opening Balances

Opening balances are normal journal entries. Do not insert rows directly into `account_balances`.

```php
$ledger->postOpeningBalance(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'opening:cash:2026',
    description: 'Opening cash balance',
    lines: [
        JournalLineData::debit($cash->id, '100000.00000000', 'USD'),
        JournalLineData::credit($openingBalanceEquity->id, '100000.00000000', 'USD'),
    ],
));
```

Internally, `postOpeningBalance()` calls `post()`. The same validation, idempotency, immutability, and balance rules apply.

## Idempotency

Every journal entry must have a unique `idempotencyKey`.

```php
$data = new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'external-event:' . $eventUuid,
    lines: [
        JournalLineData::debit($cash->id, '50.00000000', 'USD'),
        JournalLineData::credit($revenue->id, '50.00000000', 'USD'),
    ],
);

$first = $ledger->post($data);
$second = $ledger->post($data);

// Same entry is returned when the payload is identical.
$first->id === $second->id;
```

If the same key is used with a different payload, the package throws `IdempotencyConflictException`.

```php
use LedgerCore\Exceptions\IdempotencyConflictException;

try {
    $ledger->post($differentPayloadWithSameKey);
} catch (IdempotencyConflictException $exception) {
    report($exception);
}
```

### Choosing Idempotency Keys

Good keys are stable, unique, and based on the business event in the host app.

Examples:

```text
receipt:2026-000001
invoice-paid:8f5c8e93-8b8f-48c1-9d7e-6e5a0b15c1a1
webhook:stripe:evt_123
opening-balance:main-book:2026
manual-adjustment:2026-000010
```

Avoid keys based only on timestamps or random values when retry safety matters.

## Reversals

Posted entries cannot be edited or deleted. To correct a posted entry, reverse it.

```php
$reversal = $ledger->reverse(
    entry: $entry,
    reason: 'Incorrect amount posted',
);
```

The reversal service:

1. Locks the original entry.
2. Ensures it is posted.
3. Creates a new posted journal entry.
4. Uses opposite debit and credit directions.
5. Links the reversal to the original entry.
6. Marks the original entry as reversed.
7. Updates balances through normal posting.
8. Dispatches `JournalEntryReversed`.

Original entry:

```text
Dr Cash      100.00000000
Cr Revenue   100.00000000
```

Reversal entry:

```text
Dr Revenue   100.00000000
Cr Cash      100.00000000
```

## Balances

Balances are stored in `account_balances` for fast reads.

Use:

```php
$balance = $ledger->getBalance($cash);
```

Or inject `BalanceService`:

```php
use LedgerCore\Services\BalanceService;

$balance = app(BalanceService::class)->getBalance($cash);
```

### Balance Formula

For debit-normal accounts:

```text
balance = debit_total - credit_total
```

For credit-normal accounts:

```text
balance = credit_total - debit_total
```

### Negative Balances

By default, the package prevents negative balances when `prevent_negative_balances_by_default` is enabled.

Allow a specific account to go negative:

```php
$account = $ledger->createAccount(new CreateAccountData(
    ledgerEntityId: $entity->id,
    name: 'Temporary Clearing',
    type: AccountType::ASSET,
    normalBalance: NormalBalance::DEBIT,
    allowNegative: true,
));
```

## Reports

Use `LedgerManager` for common reports:

```php
$trialBalance = $ledger->getTrialBalance($entity->id);

$statement = $ledger->getStatement(
    accountId: $cash->id,
    from: now()->startOfMonth(),
    to: now()->endOfMonth(),
);
```

Use `LedgerReportService` for more control:

```php
use LedgerCore\Services\LedgerReportService;

$reports = app(LedgerReportService::class);

$trialBalance = $reports->trialBalance(
    entityId: $entity->id,
    from: now()->startOfYear(),
    to: now(),
);

$generalLedger = $reports->generalLedger($entity->id, [
    'account_id' => $cash->id,
    'from' => now()->startOfMonth(),
    'to' => now()->endOfMonth(),
    'reference_type' => 'receipt',
    'per_page' => 100,
]);

$statement = $reports->accountStatement(
    accountId: $cash->id,
    from: now()->subMonth(),
    to: now(),
);

$balances = $reports->accountBalances($entity->id, [
    'currency' => 'USD',
]);
```

### Trial Balance

Returns account-level totals:

- account id
- code
- name
- type
- currency
- debit total
- credit total
- balance

### General Ledger

Returns journal lines with entry and account information, filterable by:

- account
- date range
- reference type
- reference id

### Account Statement

Returns movements for one account over a period.

## Filament Integration

Filament support is optional. The package can run without Filament installed.

If Filament is installed, register the plugin:

```php
use Filament\Panel;
use LedgerCore\LedgerCorePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(LedgerCorePlugin::make());
}
```

The plugin registers:

- `LedgerEntityResource`
- `LedgerAccountResource`
- `JournalEntryResource`
- `TrialBalancePage`
- `GeneralLedgerPage`
- `AccountStatementPage`
- `LedgerStatsWidget`
- `AccountBalanceOverviewWidget`

### Filament Safety

- Destructive actions are omitted or require confirmation.
- Posted journal entries are not editable unless metadata updates are explicitly allowed.
- Posted journal lines are read-only.
- Manual journal creation is disabled by default.
- Account `type`, `normal_balance`, and `currency` are disabled when the account already has lines.

Enable manual journal creation only if your operators understand double-entry posting:

```php
'posting' => [
    'allow_manual_entries_from_filament' => true,
],
```

## Host Application Use Cases

The package does not know your domain. The host app should create posting services or recipes that convert business events into journal entries.

### Use Case: Generic Cash Receipt

Host application concept:

```text
An external event says money was received.
```

Ledger entry:

```text
Dr Cash
Cr Revenue or Clearing
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'cash-received:' . $event->uuid,
    referenceType: 'cash_received',
    referenceId: (string) $event->id,
    description: 'Cash received',
    lines: [
        JournalLineData::debit($cashAccountId, '500.00000000', 'USD'),
        JournalLineData::credit($clearingAccountId, '500.00000000', 'USD'),
    ],
));
```

### Use Case: Fee Split

Host application concept:

```text
A gross amount is received, part is principal and part is fee income.
```

Ledger entry:

```text
Dr Cash
Cr Clearing
Cr Fee Revenue
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'gross-receipt:' . $event->uuid,
    referenceType: 'gross_receipt',
    referenceId: (string) $event->id,
    description: 'Gross receipt with fee split',
    lines: [
        JournalLineData::debit($cashAccountId, '1000.00000000', 'USD'),
        JournalLineData::credit($clearingAccountId, '980.00000000', 'USD'),
        JournalLineData::credit($feeRevenueAccountId, '20.00000000', 'USD'),
    ],
));
```

### Use Case: Expense Recognition

Ledger entry:

```text
Dr Expense
Cr Cash or Payable
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'expense:' . $expense->uuid,
    referenceType: 'expense',
    referenceId: (string) $expense->id,
    description: 'Expense recognized',
    lines: [
        JournalLineData::debit($expenseAccountId, '75.00000000', 'USD'),
        JournalLineData::credit($cashAccountId, '75.00000000', 'USD'),
    ],
));
```

### Use Case: Liability Settlement

Ledger entry:

```text
Dr Liability
Cr Cash
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'liability-settlement:' . $settlement->uuid,
    referenceType: 'liability_settlement',
    referenceId: (string) $settlement->id,
    description: 'Liability settled',
    lines: [
        JournalLineData::debit($liabilityAccountId, '300.00000000', 'USD'),
        JournalLineData::credit($cashAccountId, '300.00000000', 'USD'),
    ],
));
```

### Use Case: Currency Exchange

Host application concept:

```text
The application exchanges one currency for another and may recognize spread or fee income.
```

Important ledger design:

- Use separate accounts per currency when possible.
- Provide `baseAmount` and `exchangeRate` for multi-currency entries.
- Keep exchange quote, rate source, and execution details in the host application.
- Store only generic `referenceType`, `referenceId`, and metadata in the ledger entry.

Example:

```text
A user gives 1,000 USD.
The app gives 4,800 LYD.
The base currency is USD.
The 4,800 LYD output is valued at 990 USD.
The app recognizes 10 USD exchange revenue.
```

Ledger entry:

```text
Dr USD Cash              1,000 USD base 1,000 USD
Cr LYD Cash              4,800 LYD base   990 USD
Cr Exchange Revenue         10 USD base    10 USD
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'currency-exchange:' . $exchange->uuid,
    referenceType: 'currency_exchange',
    referenceId: (string) $exchange->id,
    description: 'Currency exchange executed',
    lines: [
        JournalLineData::debit(
            accountId: $usdCashAccountId,
            amount: '1000.00000000',
            currency: 'USD',
            baseAmount: '1000.00000000',
            exchangeRate: '1.000000000000',
            memo: 'USD received',
        ),
        JournalLineData::credit(
            accountId: $lydCashAccountId,
            amount: '4800.00000000',
            currency: 'LYD',
            baseAmount: '990.00000000',
            exchangeRate: '0.206250000000',
            memo: 'LYD delivered',
        ),
        JournalLineData::credit(
            accountId: $exchangeRevenueAccountId,
            amount: '10.00000000',
            currency: 'USD',
            baseAmount: '10.00000000',
            exchangeRate: '1.000000000000',
            memo: 'Exchange spread',
        ),
    ],
    metadata: [
        'rate_source' => 'host_application',
        'quoted_rate' => '4.800000000000',
    ],
));
```

The ledger package does not contain an exchange model, quote engine, rate provider, or settlement workflow. Those belong in the host app.

### Use Case: Order Workflow

Host application concept:

```text
An order moves through authorization, payment capture, fulfillment, revenue recognition, and possible refund.
```

The host app can post one journal entry per meaningful accounting event.

#### Order Payment Captured

Ledger entry:

```text
Dr Cash or Payment Clearing
Cr Customer Deposits or Deferred Revenue
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'order-payment-captured:' . $order->uuid,
    referenceType: 'order',
    referenceId: (string) $order->id,
    description: 'Order payment captured',
    lines: [
        JournalLineData::debit(
            accountId: $paymentClearingAccountId,
            amount: $order->total_amount,
            currency: $order->currency,
        ),
        JournalLineData::credit(
            accountId: $customerDepositAccountId,
            amount: $order->total_amount,
            currency: $order->currency,
        ),
    ],
));
```

#### Order Fulfilled And Revenue Recognized

Ledger entry:

```text
Dr Customer Deposits or Deferred Revenue
Cr Sales Revenue
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'order-fulfilled:' . $order->uuid,
    referenceType: 'order',
    referenceId: (string) $order->id,
    description: 'Order fulfilled and revenue recognized',
    lines: [
        JournalLineData::debit(
            accountId: $customerDepositAccountId,
            amount: $order->total_amount,
            currency: $order->currency,
        ),
        JournalLineData::credit(
            accountId: $salesRevenueAccountId,
            amount: $order->total_amount,
            currency: $order->currency,
        ),
    ],
));
```

#### Order Refund

Ledger entry:

```text
Dr Refunds or Sales Returns
Cr Cash or Payment Clearing
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'order-refund:' . $refund->uuid,
    referenceType: 'order_refund',
    referenceId: (string) $refund->id,
    description: 'Order refund posted',
    lines: [
        JournalLineData::debit(
            accountId: $salesReturnsAccountId,
            amount: $refund->amount,
            currency: $refund->currency,
        ),
        JournalLineData::credit(
            accountId: $paymentClearingAccountId,
            amount: $refund->amount,
            currency: $refund->currency,
        ),
    ],
));
```

This package does not decide when an order is fulfilled or whether revenue should be recognized at capture, shipment, delivery, or acceptance. The host application owns that policy.

### Use Case: Marketplace Or Platform Payout

Host application concept:

```text
A platform collects funds, keeps a fee, and pays out the remaining amount to another party.
```

Ledger entry when funds are collected:

```text
Dr Cash
Cr Payable To Counterparty
Cr Platform Fee Revenue
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'platform-collection:' . $collection->uuid,
    referenceType: 'platform_collection',
    referenceId: (string) $collection->id,
    description: 'Platform collection with fee',
    lines: [
        JournalLineData::debit($cashAccountId, '1000.00000000', 'USD'),
        JournalLineData::credit($counterpartyPayableAccountId, '950.00000000', 'USD'),
        JournalLineData::credit($platformFeeRevenueAccountId, '50.00000000', 'USD'),
    ],
));
```

Ledger entry when payout is sent:

```text
Dr Payable To Counterparty
Cr Cash
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'platform-payout:' . $payout->uuid,
    referenceType: 'platform_payout',
    referenceId: (string) $payout->id,
    description: 'Platform payout sent',
    lines: [
        JournalLineData::debit($counterpartyPayableAccountId, '950.00000000', 'USD'),
        JournalLineData::credit($cashAccountId, '950.00000000', 'USD'),
    ],
));
```

### Use Case: Customer Prepayment And Later Application

Host application concept:

```text
A customer pays before the product or service is delivered.
```

When money is received:

```text
Dr Cash
Cr Customer Deposits
```

When the deposit is applied:

```text
Dr Customer Deposits
Cr Revenue
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'prepayment-received:' . $payment->uuid,
    referenceType: 'prepayment',
    referenceId: (string) $payment->id,
    description: 'Customer prepayment received',
    lines: [
        JournalLineData::debit($cashAccountId, $payment->amount, $payment->currency),
        JournalLineData::credit($customerDepositAccountId, $payment->amount, $payment->currency),
    ],
));

$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'prepayment-applied:' . $application->uuid,
    referenceType: 'prepayment_application',
    referenceId: (string) $application->id,
    description: 'Customer prepayment applied',
    lines: [
        JournalLineData::debit($customerDepositAccountId, $application->amount, $application->currency),
        JournalLineData::credit($revenueAccountId, $application->amount, $application->currency),
    ],
));
```

### Use Case: Internal Reclassification

Host application concept:

```text
An amount was posted to the right side of the ledger but needs to move between accounts.
```

Ledger entry:

```text
Dr Correct Account
Cr Original Account
```

Code:

```php
$ledger->post(new JournalEntryData(
    ledgerEntityId: $entity->id,
    idempotencyKey: 'reclassification:' . $adjustment->uuid,
    referenceType: 'reclassification',
    referenceId: (string) $adjustment->id,
    description: 'Internal account reclassification',
    lines: [
        JournalLineData::debit($correctAccountId, '125.00000000', 'USD'),
        JournalLineData::credit($originalAccountId, '125.00000000', 'USD'),
    ],
    metadata: [
        'approved_by' => $adjustment->approved_by,
    ],
));
```

Use a reversal instead when the original posted journal entry itself is wrong and should be explicitly negated.

### Use Case: Host-Specific Workflow Service

Create a service in your application:

```php
namespace App\Services;

use App\Models\Receipt;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Data\JournalLineData;
use LedgerCore\Services\LedgerManager;

final readonly class ReceiptPostingService
{
    public function __construct(private LedgerManager $ledger)
    {
    }

    public function postReceipt(Receipt $receipt): void
    {
        $this->ledger->post(new JournalEntryData(
            ledgerEntityId: $receipt->ledger_entity_id,
            idempotencyKey: 'receipt:' . $receipt->uuid,
            referenceType: 'receipt',
            referenceId: (string) $receipt->id,
            description: 'Receipt posted',
            lines: [
                JournalLineData::debit(
                    accountId: $receipt->cash_account_id,
                    amount: $receipt->amount,
                    currency: $receipt->currency,
                ),
                JournalLineData::credit(
                    accountId: $receipt->clearing_account_id,
                    amount: $receipt->amount,
                    currency: $receipt->currency,
                ),
            ],
        ));
    }
}
```

This service belongs in your application, not in `ledger-core`.

## Extending The Package

### Custom Posting Validator

Bind `PostingValidatorContract` in your application:

```php
use Illuminate\Support\ServiceProvider;
use LedgerCore\Contracts\PostingValidatorContract;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PostingValidatorContract::class,
            \App\Ledger\AppPostingValidator::class,
        );
    }
}
```

Example validator:

```php
namespace App\Ledger;

use LedgerCore\Contracts\PostingValidatorContract;
use LedgerCore\Data\JournalEntryData;
use LedgerCore\Exceptions\LedgerException;

final class AppPostingValidator implements PostingValidatorContract
{
    public function validate(JournalEntryData $entry): void
    {
        if ($entry->idempotencyKey === '') {
            throw new LedgerException('Idempotency key is required.');
        }

        if (count($entry->lines) > 100) {
            throw new LedgerException('Journal entry has too many lines.');
        }
    }
}
```

### Custom Account Resolver

Bind `AccountResolverContract` if your app needs custom account lookup.

```php
use LedgerCore\Contracts\AccountResolverContract;

$this->app->bind(AccountResolverContract::class, AppAccountResolver::class);
```

### Currency Converter

Bind `CurrencyConverterContract` if your app wants package-level conversion utilities.

```php
use LedgerCore\Contracts\CurrencyConverterContract;

$this->app->bind(CurrencyConverterContract::class, AppCurrencyConverter::class);
```

Posting itself does not guess exchange rates. Your app should provide `baseAmount` and `exchangeRate` when needed.

### Custom Models

You may replace model classes through config:

```php
'models' => [
    'account' => App\Models\LedgerAccount::class,
],
```

Custom models should extend the package models unless you are deliberately replacing behavior.

## Events

The package dispatches Laravel events:

### `JournalEntryPosting`

Dispatched before a journal entry is created, after idempotency has been checked.

```php
use LedgerCore\Events\JournalEntryPosting;

Event::listen(JournalEntryPosting::class, function (JournalEntryPosting $event): void {
    logger()->info('Posting journal entry', [
        'idempotency_key' => $event->data->idempotencyKey,
    ]);
});
```

### `JournalEntryPosted`

Dispatched after the journal entry, lines, and balances have been created.

```php
use LedgerCore\Events\JournalEntryPosted;

Event::listen(JournalEntryPosted::class, function (JournalEntryPosted $event): void {
    logger()->info('Journal entry posted', [
        'entry_id' => $event->entry->id,
    ]);
});
```

### `JournalEntryReversed`

Dispatched after a reversal entry is posted and the original entry is marked reversed.

```php
use LedgerCore\Events\JournalEntryReversed;

Event::listen(JournalEntryReversed::class, function (JournalEntryReversed $event): void {
    logger()->info('Journal entry reversed', [
        'original_id' => $event->original->id,
        'reversal_id' => $event->reversal->id,
    ]);
});
```

## Exceptions

Common exceptions:

- `LedgerException`
- `UnbalancedJournalEntryException`
- `DuplicateJournalEntryException`
- `IdempotencyConflictException`
- `AccountCurrencyMismatchException`
- `AccountNotPostableException`
- `InsufficientBalanceException`
- `InvalidReversalException`

Example:

```php
use LedgerCore\Exceptions\AccountCurrencyMismatchException;
use LedgerCore\Exceptions\InsufficientBalanceException;
use LedgerCore\Exceptions\UnbalancedJournalEntryException;

try {
    $ledger->post($entryData);
} catch (UnbalancedJournalEntryException $exception) {
    // Show validation feedback or fail the job permanently.
} catch (AccountCurrencyMismatchException $exception) {
    // Fix account mapping or currency selection.
} catch (InsufficientBalanceException $exception) {
    // Stop the workflow or route it to review.
}
```

## Testing

From the package directory:

```bash
composer install
vendor/bin/pest
```

The test suite covers:

- Entity creation
- Account creation
- Balanced posting
- Unbalanced posting rejection
- Inactive account rejection
- Non-postable account rejection
- Currency mismatch rejection
- Debit-normal balances
- Credit-normal balances
- Idempotent reposting
- Idempotency conflicts
- Reversal posting
- Opposite reversal lines
- Balance updates after reversal
- Posted line immutability
- Account currency immutability after posting
- Trial balance reports
- Account statement reports
- Opening balance posting

## Operational Guidance

### Do Not Update Balances Manually

Never run application code like this:

```php
$account->balance()->update(['balance' => '100.00000000']);
```

Always post a journal entry.

### Do Not Edit Posted Lines

Posted lines are the audit record. If something is wrong, reverse the entry and post a corrected entry.

### Keep Account Mapping In Your App

Your app should decide which accounts are used for each workflow.

Good pattern:

```php
$accounts = app(AccountMappingService::class)->forReceipt($receipt);

$ledger->post(new JournalEntryData(
    ledgerEntityId: $receipt->ledger_entity_id,
    idempotencyKey: 'receipt:' . $receipt->uuid,
    lines: [
        JournalLineData::debit($accounts->cash, $receipt->amount, $receipt->currency),
        JournalLineData::credit($accounts->clearing, $receipt->amount, $receipt->currency),
    ],
));
```

Avoid putting receipt, invoice, transfer, wallet, or provider logic inside the ledger package.

### Use Database Transactions Around Host Workflow State

If your application updates business state and posts to the ledger, wrap both in one transaction when possible.

```php
DB::transaction(function () use ($receipt, $ledger): void {
    $receipt->markAsPosted();

    $ledger->post(new JournalEntryData(
        ledgerEntityId: $receipt->ledger_entity_id,
        idempotencyKey: 'receipt:' . $receipt->uuid,
        lines: [
            JournalLineData::debit($receipt->cash_account_id, $receipt->amount, $receipt->currency),
            JournalLineData::credit($receipt->clearing_account_id, $receipt->amount, $receipt->currency),
        ],
    ));
});
```

The ledger posting service also runs its own transaction. Laravel safely nests transactions using savepoints where supported.

### Queue And Webhook Safety

For jobs and webhooks:

- Use a deterministic idempotency key.
- Catch idempotency conflicts.
- Retry only transient database failures.
- Treat unbalanced entries as application bugs.
- Log the `reference_type`, `reference_id`, and `idempotency_key`.

## FAQ

### Does this package create a chart of accounts?

No. The package provides account storage and posting rules. Your application creates the accounts it needs.

### Can I use this package for wallets?

Yes, but wallet concepts belong in your application. The package should only receive journal entries against generic accounts.

### Can I delete a posted journal entry?

No. Reverse it.

### Can I edit a posted amount?

No. Reverse the entry and post a corrected one.

### Can I update cached balances directly?

No. Cached balances are maintained by `JournalPostingService`.

### Can one journal entry include accounts from multiple entities?

Only if `posting.allow_cross_entity_entries` is enabled. The default is `false`.

### Can accounts have no currency?

Yes. A null account currency means the package will accept line currencies for that account.

### Can I use UUIDs externally?

Yes. Core tables include UUID columns for public references while keeping integer primary keys for database relationships.

### How should I store business references?

Use `reference_type` and `reference_id`.

```php
referenceType: 'receipt',
referenceId: (string) $receipt->id,
```

These are intentionally strings so the host application can reference any model or external event.

### Why are amounts strings?

PHP floats are unsafe for money. This package uses decimal strings, decimal database columns, and `bcmath`.

## Minimal Service Reference

### `LedgerManager`

```php
$ledger->createEntity(...);
$ledger->createAccount(CreateAccountData $data);
$ledger->post(JournalEntryData $data);
$ledger->postOpeningBalance(JournalEntryData $data);
$ledger->reverse(JournalEntry $entry, ?string $reason = null);
$ledger->getBalance(LedgerAccount|int|string $account);
$ledger->getStatement($accountId, $from = null, $to = null);
$ledger->getTrialBalance($entityId, $from = null, $to = null);
```

### `AccountService`

```php
$accounts->create(CreateAccountData $data);
$accounts->findOrFail($id);
$accounts->ensurePostable($account);
$accounts->ensureCurrencyCompatible($account, $currency);
```

### `JournalPostingService`

```php
$posting->post(JournalEntryData $data);
```

### `BalanceService`

```php
$balances->initializeBalance($account);
$balances->applyLine($line);
$balances->getBalance($account);
$balances->assertSufficientBalance($account, $amount);
```

### `ReversalService`

```php
$reversals->reverse($entry, $reason);
```

### `LedgerReportService`

```php
$reports->trialBalance($entityId, $from = null, $to = null);
$reports->generalLedger($entityId, $filters);
$reports->accountStatement($accountId, $from = null, $to = null);
$reports->accountBalances($entityId, $filters);
```

## License

MIT
