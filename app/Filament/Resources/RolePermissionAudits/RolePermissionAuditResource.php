<?php

namespace App\Filament\Resources\RolePermissionAudits;

use App\Filament\Resources\RolePermissionAudits\Schemas\RolePermissionAuditForm;
use App\Filament\Resources\RolePermissionAudits\Tables\RolePermissionAuditsTable;
use App\Models\RolePermissionAudit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RolePermissionAuditResource extends Resource
{
    protected static ?string $model = RolePermissionAudit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::LockClosed;
    protected static ?string $navigationLabel = 'Permissions Audit';

    public static function form(Schema $schema): Schema
    {
        return RolePermissionAuditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolePermissionAuditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
        ];
    }
}
