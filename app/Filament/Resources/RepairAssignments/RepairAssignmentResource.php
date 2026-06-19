<?php

namespace App\Filament\Resources\RepairAssignments;

use App\Filament\Resources\RepairAssignments\Pages\CreateRepairAssignment;
use App\Filament\Resources\RepairAssignments\Pages\EditRepairAssignment;
use App\Filament\Resources\RepairAssignments\Pages\ListRepairAssignments;
use App\Filament\Resources\RepairAssignments\Schemas\RepairAssignmentForm;
use App\Filament\Resources\RepairAssignments\Tables\RepairAssignmentsTable;
use App\Models\RepairAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RepairAssignmentResource extends Resource
{
    protected static ?string $model = RepairAssignment::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-user-group';

    protected static string|null|\UnitEnum $navigationGroup = 'Workshop';

    protected static ?string $navigationLabel = 'Repair Assignments';

    public static function form(Schema $schema): Schema
    {
        return RepairAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairAssignmentsTable::configure($table);
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
            'index' => ListRepairAssignments::route('/'),
            'create' => CreateRepairAssignment::route('/create'),
            'edit' => EditRepairAssignment::route('/{record}/edit'),
        ];
    }
}
