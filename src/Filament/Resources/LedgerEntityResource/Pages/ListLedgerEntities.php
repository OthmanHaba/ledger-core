<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerEntityResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LedgerCore\Filament\Resources\LedgerEntityResource;

class ListLedgerEntities extends ListRecords
{
    protected static string $resource = LedgerEntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
