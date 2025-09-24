<?php

namespace App\Filament\Resources\Requisitions\Schemas;

use App\Models\ExpenseType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RequisitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('requester_id')
                ->default(auth()->id())
                ->dehydrated(true),

            // Optional: show the requester name and role for transparency
            TextInput::make('requester_name')
                ->label('Requester')
                ->default(fn () => auth()->user()?->name)
                ->disabled()
                ->dehydrated(false),

            Select::make('expense_type_id')->label('Expense type')->options(
                ExpenseType::where('is_active', true)->pluck('name', 'id')->toArray()
            )->required()->searchable(),
            TextInput::make('amount')->numeric()->required(),
            Select::make('currency_id')
                ->label('Currency')
                ->relationship('currency', 'code') // assuming 'code' is like USD, KES, etc.
                ->searchable()
                ->required(),
            Textarea::make('description')->rows(3),
            FileUpload::make('proofs_temp')->multiple()->directory('requisition-proofs')->label('Attach proofs'),
        ]);
    }
}
