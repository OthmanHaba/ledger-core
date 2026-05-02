<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerEntityResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use LedgerCore\Filament\Resources\LedgerEntityResource;

class ViewLedgerEntity extends ViewRecord
{
    protected static string $resource = LedgerEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
