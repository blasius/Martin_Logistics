<?php

namespace App\Filament\Resources\PoItems;

use App\Filament\Resources\PoItems\Pages\CreatePoItem;
use App\Filament\Resources\PoItems\Pages\EditPoItem;
use App\Filament\Resources\PoItems\Pages\ListPoItems;
use App\Filament\Resources\PoItems\Schemas\PoItemForm;
use App\Filament\Resources\PoItems\Tables\PoItemsTable;
use App\Models\PoItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PoItemResource extends Resource
{
    protected static ?string $model = PoItem::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    protected static string|null|\UnitEnum $navigationGroup = 'Workshop';

    protected static ?string $navigationLabel = 'PO Items';

    public static function form(Schema $schema): Schema
    {
        return PoItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PoItemsTable::configure($table);
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
            'index' => ListPoItems::route('/'),
            'create' => CreatePoItem::route('/create'),
            'edit' => EditPoItem::route('/{record}/edit'),
        ];
    }
}
