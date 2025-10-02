<?php

namespace App\Filament\Resources\VehicleInsurances;

use App\Filament\Resources\VehicleInsurances\Pages\CreateVehicleInsurance;
use App\Filament\Resources\VehicleInsurances\Pages\EditVehicleInsurance;
use App\Filament\Resources\VehicleInsurances\Pages\ListVehicleInsurances;
use App\Filament\Resources\VehicleInsurances\Schemas\VehicleInsuranceForm;
use App\Filament\Resources\VehicleInsurances\Tables\VehicleInsurancesTable;
use App\Models\VehicleInsurance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VehicleInsuranceResource extends Resource
{
    protected static ?string $model = VehicleInsurance::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedShieldCheck;
    //protected static string|null|\UnitEnum $navigationGroup = 'Fleet';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Insurance';
    protected static ?string $pluralLabel = 'Insurances';

    public static function form(Schema $schema): Schema
    {
        return VehicleInsuranceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleInsurancesTable::configure($table);
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
            'index' => ListVehicleInsurances::route('/'),
            'create' => CreateVehicleInsurance::route('/create'),
            'edit' => EditVehicleInsurance::route('/{record}/edit'),
        ];
    }
}
