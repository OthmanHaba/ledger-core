<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\JournalEntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Filament\Resources\JournalEntryResource;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Services\ReversalService;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reverse')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === JournalEntryStatus::POSTED)
                ->action(fn (): JournalEntry => app(ReversalService::class)->reverse($this->record)),
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status !== JournalEntryStatus::POSTED || config('ledger.posting.allow_posted_metadata_updates', false)),
        ];
    }
}
