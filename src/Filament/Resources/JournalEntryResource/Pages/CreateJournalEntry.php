<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\JournalEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Filament\Resources\JournalEntryResource;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! config('ledger.posting.allow_manual_entries_from_filament', false)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Manual journal entry creation is disabled.',
            ]);
        }

        $data['status'] = JournalEntryStatus::DRAFT->value;

        return $data;
    }
}
