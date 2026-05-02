<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LedgerCore\Filament\Resources\LedgerAccountResource;

class CreateLedgerAccount extends CreateRecord
{
    protected static string $resource = LedgerAccountResource::class;
}
