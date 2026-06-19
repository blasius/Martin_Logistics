<?php

namespace App\Filament\Resources\RepairRequests;

use App\Filament\Resources\RepairRequests\Pages\CreateRepairRequest;
use App\Filament\Resources\RepairRequests\Pages\EditRepairRequest;
use App\Filament\Resources\RepairRequests\Pages\ListRepairRequests;
use App\Filament\Resources\RepairRequests\Schemas\RepairRequestForm;
use App\Filament\Resources\RepairRequests\Tables\RepairRequestsTable;
use App\Models\RepairRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RepairRequestResource extends Resource
{
    protected static ?string $model = RepairRequest::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-wrench';

    protected static string|null|\UnitEnum $navigationGroup = 'Workshop';

    protected static ?string $navigationLabel = 'Repair Requests';

    public static function form(Schema $schema): Schema
    {
        return RepairRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairRequestsTable::configure($table);
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
            'index' => ListRepairRequests::route('/'),
            'create' => CreateRepairRequest::route('/create'),
            'edit' => EditRepairRequest::route('/{record}/edit'),
        ];
    }
}
