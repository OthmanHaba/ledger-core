<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerAccountResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use LedgerCore\Filament\Resources\LedgerAccountResource;

class ViewLedgerAccount extends ViewRecord
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
