<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('base_currency_id')
                ->relationship('baseCurrency', 'code')
                ->searchable()
                ->required(),

            Select::make('target_currency_id')
                ->relationship('targetCurrency', 'code')
                ->searchable()
                ->required(),

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
