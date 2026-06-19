<?php

namespace App\Filament\Resources\RepairReleases;

use App\Filament\Resources\RepairReleases\Pages\CreateRepairRelease;
use App\Filament\Resources\RepairReleases\Pages\EditRepairRelease;
use App\Filament\Resources\RepairReleases\Pages\ListRepairReleases;
use App\Filament\Resources\RepairReleases\Schemas\RepairReleaseForm;
use App\Filament\Resources\RepairReleases\Tables\RepairReleasesTable;
use App\Models\RepairRelease;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RepairReleaseResource extends Resource
{
    protected static ?string $model = RepairRelease::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-check-circle';

    protected static string|null|\UnitEnum $navigationGroup = 'Workshop';

    protected static ?string $navigationLabel = 'Repair Releases';

    public static function form(Schema $schema): Schema
    {
        return RepairReleaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairReleasesTable::configure($table);
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
            'index' => ListRepairReleases::route('/'),
            'create' => CreateRepairRelease::route('/create'),
            'edit' => EditRepairRelease::route('/{record}/edit'),
        ];
    }
}
