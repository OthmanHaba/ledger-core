<?php

declare(strict_types=1);

namespace LedgerCore;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LedgerCore\Filament\Pages\AccountStatementPage;
use LedgerCore\Filament\Pages\GeneralLedgerPage;
use LedgerCore\Filament\Pages\TrialBalancePage;
use LedgerCore\Filament\Resources\JournalEntryResource;
use LedgerCore\Filament\Resources\LedgerAccountResource;
use LedgerCore\Filament\Resources\LedgerEntityResource;
use LedgerCore\Filament\Widgets\AccountBalanceOverviewWidget;
use LedgerCore\Filament\Widgets\LedgerStatsWidget;

final class LedgerCorePlugin implements Plugin
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'ledger-core';
    }

    public function register(Panel $panel): void
    {
        if (! config('ledger.filament.enabled', true)) {
            return;
        }

        $panel
            ->resources([
                LedgerEntityResource::class,
                LedgerAccountResource::class,
                JournalEntryResource::class,
            ])
            ->pages([
                TrialBalancePage::class,
                GeneralLedgerPage::class,
                AccountStatementPage::class,
            ])
            ->widgets([
                LedgerStatsWidget::class,
                AccountBalanceOverviewWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
