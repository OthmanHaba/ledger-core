<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\LedgerEntityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LedgerCore\Filament\Resources\LedgerEntityResource;

class CreateLedgerEntity extends CreateRecord
{
    protected static string $resource = LedgerEntityResource::class;
}
