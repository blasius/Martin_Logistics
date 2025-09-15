<?php

namespace App\Filament\Resources\Trailers;

use App\Filament\Resources\Trailers\Pages\CreateTrailer;
use App\Filament\Resources\Trailers\Pages\EditTrailer;
use App\Filament\Resources\Trailers\Pages\ListTrailers;
use App\Filament\Resources\Trailers\Schemas\TrailerForm;
use App\Filament\Resources\Trailers\Tables\TrailersTable;
use App\Models\Trailer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrailerResource extends Resource
{
    protected static ?string $model = Trailer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|null|\UnitEnum $navigationGroup = 'Fleet';

    public static function form(Schema $schema): Schema
    {
        return TrailerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrailersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrailers::route('/'),
            'create' => CreateTrailer::route('/create'),
            'edit' => EditTrailer::route('/{record}/edit'),
        ];
    }
}
