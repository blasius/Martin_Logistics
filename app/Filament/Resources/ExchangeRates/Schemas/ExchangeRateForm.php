<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('base_currency')
                ->label('Base Currency (ISO)')
                ->required()
                ->maxLength(3)
                ->default('RWF')
                ->disabled(fn ($record) => $record !== null),

            TextInput::make('target_currency')
                ->label('Target Currency (ISO)')
                ->required()
                ->maxLength(3),

            TextInput::make('rate')
                ->numeric()
                ->required()
                ->step('0.000001'),

            DateTimePicker::make('valid_from')
                ->required(),

            DateTimePicker::make('valid_to')
                ->label('Valid To')
                ->nullable(),

        ]);
    }
}
