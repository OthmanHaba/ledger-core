<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LedgerCore\Filament\Resources\LedgerAccountResource;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
