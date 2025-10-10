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

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|null|\UnitEnum $navigationGroup = 'System Logs';
    protected static ?string $navigationLabel = 'Role & Permission Audits';

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
            'index' => Pages\ListRolePermissionAudits::route('/'),
        ];
    }
}
