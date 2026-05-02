<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerAccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use LedgerCore\Filament\Resources\LedgerAccountResource;

class EditLedgerAccount extends EditRecord
{
    protected static string $resource = LedgerAccountResource::class;
}
