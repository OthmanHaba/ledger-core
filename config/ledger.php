<?php

declare(strict_types=1);

use LedgerCore\Models\AccountBalance;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Models\JournalLine;
use LedgerCore\Models\LedgerAccount;
use LedgerCore\Models\LedgerEntity;

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
