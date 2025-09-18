<?php

namespace App\Filament\Resources\WialonUnits;

use App\Filament\Resources\WialonUnits\Pages\CreateWialonUnit;
use App\Filament\Resources\WialonUnits\Pages\EditWialonUnit;
use App\Filament\Resources\WialonUnits\Pages\ListWialonUnits;
use App\Filament\Resources\WialonUnits\Schemas\WialonUnitForm;
use App\Filament\Resources\WialonUnits\Tables\WialonUnitsTable;
use App\Models\WialonUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WialonUnitResource extends Resource
{
    protected static ?string $model = WialonUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WialonUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WialonUnitsTable::configure($table);
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
            'index' => ListWialonUnits::route('/'),
            'create' => CreateWialonUnit::route('/create'),
            'edit' => EditWialonUnit::route('/{record}/edit'),
        ];
    }
}
