<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Pages;

use Filament\Pages\Page;
use LedgerCore\Services\LedgerReportService;

class AccountStatementPage extends Page
{
    protected static string $view = 'ledger-core::filament.pages.account-statement';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    public ?int $account = null;
    public ?string $from = null;
    public ?string $to = null;

    public static function getNavigationGroup(): ?string
    {
        return config('ledger.filament.navigation_group', 'Ledger');
    }

    public function mount(?int $account = null): void
    {
        $this->account = $account;
    }

    public function getRows(): array
    {
        if ($this->account === null) {
            return [];
        }

        return app(LedgerReportService::class)
            ->accountStatement($this->account, $this->from, $this->to)
            ->all();
    }
}
