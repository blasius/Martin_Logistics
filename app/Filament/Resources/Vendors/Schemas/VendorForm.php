<?php

namespace App\Filament\Resources\Vendors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('contact')
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->maxLength(50),
            Textarea::make('address')
                ->rows(3),
            TextInput::make('tin')
                ->maxLength(100),
            TextInput::make('payment_terms')
                ->maxLength(255),
            TextInput::make('supply_categories')
                ->maxLength(255),
        ]);
    }
}
