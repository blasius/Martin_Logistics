<?php

namespace App\Filament\Resources\RolePermissionAudits\Schemas;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;

class RolePermissionAuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextColumn::make('admin.name')->label('Changed By'),
                TextColumn::make('action'),
                TextColumn::make('target_type'),
                TextColumn::make('details')->limit(60),
                TextColumn::make('created_at')->dateTime(),

            ]);
    }
}
