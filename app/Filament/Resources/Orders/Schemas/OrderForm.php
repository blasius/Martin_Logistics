<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Str;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'name')
                ->required()
                ->searchable(),

            TextInput::make('reference')
                ->label('Reference')
                ->readOnly()
                ->dehydrated(),

            TextInput::make('origin')->required(),
            TextInput::make('destination')->required(),

            DatePicker::make('pickup_date'),

            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'confirmed' => 'Confirmed',
                    'in_transit' => 'In Transit',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft'),

            TextInput::make('price')->numeric()->prefix('$'),

            Textarea::make('notes')->columnSpanFull(),
        ]);
    }
}
