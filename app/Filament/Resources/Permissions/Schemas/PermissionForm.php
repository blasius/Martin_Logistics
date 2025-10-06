<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Permission Name')
                ->required()
                ->unique(ignoreRecord: true)
                ->placeholder('e.g., manage_orders, view_reports'),
        ]);
    }
}
