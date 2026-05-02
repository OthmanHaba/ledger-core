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
use LedgerCore\Filament\Resources\LedgerEntityResource\Pages;
use LedgerCore\Models\LedgerEntity;

class LedgerEntityResource extends Resource
{
    protected static ?string $navigationIcon = null;

    public static function getModel(): string
    {
        return config('ledger.models.entity', LedgerEntity::class);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('ledger.filament.navigation_group', 'Ledger');
    }

    public static function getNavigationIcon(): string
    {
        return config('ledger.filament.navigation_icon', 'heroicon-o-calculator');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('code')->maxLength(255),
            Forms\Components\TextInput::make('type')->maxLength(255),
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('base_currency')->maxLength(3),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('base_currency')->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->visible(fn (LedgerEntity $record): bool => (bool) $record->is_active)
                    ->action(fn (LedgerEntity $record): bool => $record->forceFill(['is_active' => false])->save()),
                Action::make('view_accounts')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (LedgerEntity $record): string => LedgerAccountResource::getUrl('index', [
                        'tableFilters[ledger_entity_id][value]' => $record->getKey(),
                    ])),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerEntities::route('/'),
            'create' => Pages\CreateLedgerEntity::route('/create'),
            'view' => Pages\ViewLedgerEntity::route('/{record}'),
            'edit' => Pages\EditLedgerEntity::route('/{record}/edit'),
        ];
    }
}
