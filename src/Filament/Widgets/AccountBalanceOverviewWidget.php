<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LedgerCore\Models\AccountBalance;

class AccountBalanceOverviewWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        /** @var class-string<AccountBalance> $balanceModel */
        $balanceModel = config('ledger.models.account_balance', AccountBalance::class);
        $accountsTable = config('ledger.tables.accounts', 'ledger_accounts');

        return $balanceModel::query()
            ->select([
                DB::raw('min(' . config('ledger.tables.account_balances', 'account_balances') . '.id) as id'),
                DB::raw($accountsTable . '.ledger_entity_id as ledger_entity_id'),
                DB::raw($accountsTable . '.type as account_type'),
                DB::raw(config('ledger.tables.account_balances', 'account_balances') . '.currency as currency'),
                DB::raw('sum(' . config('ledger.tables.account_balances', 'account_balances') . '.balance) as grouped_balance'),
            ])
            ->join($accountsTable, $accountsTable . '.id', '=', config('ledger.tables.account_balances', 'account_balances') . '.ledger_account_id')
            ->groupBy($accountsTable . '.ledger_entity_id', $accountsTable . '.type', config('ledger.tables.account_balances', 'account_balances') . '.currency');
    }

    protected function getTableColumns(): array
    {
        return [
            \Filament\Tables\Columns\TextColumn::make('ledger_entity_id')->label('Entity'),
            \Filament\Tables\Columns\TextColumn::make('account_type')->label('Type'),
            \Filament\Tables\Columns\TextColumn::make('currency'),
            \Filament\Tables\Columns\TextColumn::make('grouped_balance')->label('Balance'),
        ];
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->getKey();
    }
}
