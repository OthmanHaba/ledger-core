<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Models\JournalLine;
use LedgerCore\Models\LedgerAccount;

class LedgerStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /** @var class-string<LedgerAccount> $accountModel */
        $accountModel = config('ledger.models.account', LedgerAccount::class);
        /** @var class-string<JournalEntry> $entryModel */
        $entryModel = config('ledger.models.journal_entry', JournalEntry::class);
        /** @var class-string<JournalLine> $lineModel */
        $lineModel = config('ledger.models.journal_line', JournalLine::class);

        return [
            Stat::make('Accounts', (string) $accountModel::query()->count()),
            Stat::make('Posted entries', (string) $entryModel::query()->where('status', JournalEntryStatus::POSTED)->count()),
            Stat::make('Reversed entries', (string) $entryModel::query()->where('status', JournalEntryStatus::REVERSED)->count()),
            Stat::make('Debits / credits', $lineModel::query()->count() . ' lines'),
        ];
    }
}
