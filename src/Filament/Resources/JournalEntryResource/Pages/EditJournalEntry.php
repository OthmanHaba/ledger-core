<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\JournalEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Filament\Resources\JournalEntryResource;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status === JournalEntryStatus::POSTED && ! config('ledger.posting.allow_posted_metadata_updates', false)) {
            throw ValidationException::withMessages([
                'status' => 'Posted journal entries are immutable. Create a reversal instead.',
            ]);
        }

        return $data;
    }
}
