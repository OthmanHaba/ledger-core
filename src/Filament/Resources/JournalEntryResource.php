<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use LedgerCore\Enums\JournalEntryStatus;
use LedgerCore\Filament\Resources\JournalEntryResource\Pages;
use LedgerCore\Filament\Resources\JournalEntryResource\RelationManagers\JournalLinesRelationManager;
use LedgerCore\Models\JournalEntry;
use LedgerCore\Services\ReversalService;

class JournalEntryResource extends Resource
{
    public static function getModel(): string
    {
        return config('ledger.models.journal_entry', JournalEntry::class);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('ledger.filament.navigation_group', 'Ledger');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('ledger_entity_id')
                ->relationship('entity', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->disabled(fn (?JournalEntry $record): bool => $record?->status === JournalEntryStatus::POSTED),
            Forms\Components\TextInput::make('idempotency_key')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?JournalEntry $record): bool => $record?->status === JournalEntryStatus::POSTED),
            Forms\Components\TextInput::make('reference_type')->maxLength(255),
            Forms\Components\TextInput::make('reference_id')->maxLength(255),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options(collect(JournalEntryStatus::cases())->mapWithKeys(fn (JournalEntryStatus $status): array => [$status->value => ucfirst($status->value)])->all())
                ->disabled(),
            Forms\Components\DateTimePicker::make('posted_at')->disabled(fn (?JournalEntry $record): bool => $record?->status === JournalEntryStatus::POSTED),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')->label('UUID')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('entity.name')->label('Entity')->sortable(),
                Tables\Columns\TextColumn::make('idempotency_key')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('reference_type')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('reference_id')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('posted_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (JournalEntry $record): bool => $record->status !== JournalEntryStatus::POSTED || config('ledger.posting.allow_posted_metadata_updates', false)),
                Tables\Actions\Action::make('reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn (JournalEntry $record): bool => $record->status === JournalEntryStatus::POSTED)
                    ->action(fn (JournalEntry $record): JournalEntry => app(ReversalService::class)->reverse($record)),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            JournalLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
