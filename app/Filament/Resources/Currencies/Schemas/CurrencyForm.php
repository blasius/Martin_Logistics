<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('code')
                ->label('ISO Code')
                ->required()
                ->maxLength(3)
                ->unique(ignoreRecord: true)
                ->default('USD'),

            TextInput::make('name')
                ->required(),

            TextInput::make('symbol')
                ->maxLength(5),

            Toggle::make('is_default')
                ->label('Default Currency')
                ->inline(false),
        ]);
    }
}
