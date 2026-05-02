<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Pages;

use Filament\Pages\Page;
use LedgerCore\Services\LedgerReportService;

class GeneralLedgerPage extends Page
{
    protected static string $view = 'ledger-core::filament.pages.general-ledger';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public ?int $entity = null;
    public ?int $account = null;
    public ?string $from = null;
    public ?string $to = null;
    public ?string $referenceType = null;
    public ?string $referenceId = null;

    public static function getNavigationGroup(): ?string
    {
        return config('ledger.filament.navigation_group', 'Ledger');
    }

    public function getRows(): array
    {
        if ($this->entity === null) {
            return [];
        }

        return app(LedgerReportService::class)
            ->generalLedger($this->entity, [
                'account_id' => $this->account,
                'from' => $this->from,
                'to' => $this->to,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
                'per_page' => 100,
            ])
            ->items();
    }
}
