<?php

declare(strict_types=1);

namespace LedgerCore\Filament\Resources\JournalEntryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class JournalLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('ledger_account_id')
                ->relationship('account', 'name')
                ->searchable()
                ->preload()
                ->disabled(),
            Forms\Components\TextInput::make('direction')->disabled(),
            Forms\Components\TextInput::make('amount')->disabled(),
            Forms\Components\TextInput::make('currency')->disabled(),
            Forms\Components\TextInput::make('base_amount')->disabled(),
            Forms\Components\TextInput::make('exchange_rate')->disabled(),
            Forms\Components\Textarea::make('memo')->disabled()->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->disabled()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')->label('Account')->searchable(),
                Tables\Columns\TextColumn::make('direction')->badge(),
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('base_amount'),
                Tables\Columns\TextColumn::make('exchange_rate'),
                Tables\Columns\TextColumn::make('memo')->limit(40),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}
