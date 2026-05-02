<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerEntityResource\Pages;

use Filament\Resources\Pages\EditRecord;
use LedgerCore\Filament\Resources\LedgerEntityResource;

class EditLedgerEntity extends EditRecord
{
    protected static string $resource = LedgerEntityResource::class;
}
