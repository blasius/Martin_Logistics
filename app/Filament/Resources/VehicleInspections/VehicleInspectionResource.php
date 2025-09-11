<?php

namespace App\Filament\Resources\VehicleInspections;

use App\Filament\Resources\VehicleInspections\Pages\CreateVehicleInspection;
use App\Filament\Resources\VehicleInspections\Pages\EditVehicleInspection;
use App\Filament\Resources\VehicleInspections\Pages\ListVehicleInspections;
use App\Filament\Resources\VehicleInspections\Schemas\VehicleInspectionForm;
use App\Filament\Resources\VehicleInspections\Tables\VehicleInspectionsTable;
use App\Models\VehicleInspection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VehicleInspectionResource extends Resource
{
    protected static ?string $model = VehicleInspection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
    protected static string|null|\UnitEnum $navigationGroup = 'Fleet';

    protected static ?string $recordTitleAttribute = 'Inspections';

    public static function form(Schema $schema): Schema
    {
        return VehicleInspectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleInspectionsTable::configure($table);
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
            'index' => ListVehicleInspections::route('/'),
            'create' => CreateVehicleInspection::route('/create'),
            'edit' => EditVehicleInspection::route('/{record}/edit'),
        ];
    }
}
