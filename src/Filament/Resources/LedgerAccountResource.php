<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use LedgerCore\Enums\AccountType;
use LedgerCore\Enums\NormalBalance;
use LedgerCore\Filament\Pages\AccountStatementPage;
use LedgerCore\Filament\Resources\LedgerAccountResource\Pages;
use LedgerCore\Models\LedgerAccount;

class LedgerAccountResource extends Resource
{
    public static function getModel(): string
    {
        return config('ledger.models.account', LedgerAccount::class);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('ledger.filament.navigation_group', 'Ledger');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('ledger_entity_id')
                ->relationship('entity', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('code')->maxLength(255),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('type')
                ->options(collect(AccountType::cases())->mapWithKeys(fn (AccountType $type): array => [$type->value => ucfirst($type->value)])->all())
                ->required()
                ->disabled(fn (?LedgerAccount $record): bool => $record?->journalLines()->exists() ?? false),
            Forms\Components\Select::make('normal_balance')
                ->options(collect(NormalBalance::cases())->mapWithKeys(fn (NormalBalance $balance): array => [$balance->value => ucfirst($balance->value)])->all())
                ->required()
                ->disabled(fn (?LedgerAccount $record): bool => $record?->journalLines()->exists() ?? false),
            Forms\Components\TextInput::make('currency')
                ->maxLength(3)
                ->disabled(fn (?LedgerAccount $record): bool => $record?->journalLines()->exists() ?? false),
            Forms\Components\TextInput::make('counterparty_type')->maxLength(255),
            Forms\Components\TextInput::make('counterparty_id')->maxLength(255),
            Forms\Components\Toggle::make('is_control_account')->default(false),
            Forms\Components\Toggle::make('is_postable')->default(true),
            Forms\Components\Toggle::make('allow_negative')->default(false),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('entity.name')->label('Entity')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('normal_balance')->badge()->sortable(),
                Tables\Columns\TextColumn::make('currency')->sortable(),
                Tables\Columns\TextColumn::make('balance.balance')->label('Current balance')->sortable(),
                Tables\Columns\IconColumn::make('is_postable')->boolean()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->visible(fn (LedgerAccount $record): bool => (bool) $record->is_active)
                    ->action(fn (LedgerAccount $record): bool => $record->forceFill(['is_active' => false])->save()),
                Action::make('statement')
                    ->icon('heroicon-o-document-chart-bar')
                    ->url(fn (LedgerAccount $record): string => AccountStatementPage::getUrl(['account' => $record->getKey()])),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerAccounts::route('/'),
            'create' => Pages\CreateLedgerAccount::route('/create'),
            'view' => Pages\ViewLedgerAccount::route('/{record}'),
            'edit' => Pages\EditLedgerAccount::route('/{record}/edit'),
        ];
    }
}
