<?php

namespace App\Filament\Resources\ExpenseTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExpenseTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100),
            Textarea::make('description')
                ->rows(3)
                ->maxLength(500),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }
}
