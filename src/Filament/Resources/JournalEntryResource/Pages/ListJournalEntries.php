<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\JournalEntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LedgerCore\Filament\Resources\JournalEntryResource;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return config('ledger.posting.allow_manual_entries_from_filament', false)
            ? [Actions\CreateAction::make()]
            : [];
    }
}
