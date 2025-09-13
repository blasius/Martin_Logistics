<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('contact_person')
                    ->label('Contact Person'),

                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->required(),

                TextInput::make('email')
                    ->email(),

                TextInput::make('address'),

                Select::make('type')
                    ->options([
                        'company' => 'Company',
                        'individual' => 'Individual',
                    ])
                    ->default('company')
                    ->required()
                    ->live(), // important: refreshes form dynamically

               TextInput::make('tin')
                    ->label('TIN Number')
                    ->visible(fn ($get) => $get('type') === 'company')
                    ->required(fn ($get) => $get('type') === 'company'),
            ]);
    }
}
