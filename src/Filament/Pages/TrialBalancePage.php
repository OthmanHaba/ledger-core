<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Pages;

use Filament\Pages\Page;
use LedgerCore\Services\LedgerReportService;

class TrialBalancePage extends Page
{
    protected static string $view = 'ledger-core::filament.pages.trial-balance';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    public ?int $entity = null;
    public ?string $from = null;
    public ?string $to = null;
    public ?string $currency = null;

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
            ->trialBalance($this->entity, $this->from, $this->to)
            ->when($this->currency, fn ($rows) => $rows->where('currency', $this->currency))
            ->values()
            ->all();
    }
}
