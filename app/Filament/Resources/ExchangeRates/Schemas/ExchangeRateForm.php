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

            DateTimePicker::make('valid_from')
                ->required()
                ->label('Valid From'),

            TextInput::make('rate')
                ->numeric()
                ->required(),

        ]);
    }
}
