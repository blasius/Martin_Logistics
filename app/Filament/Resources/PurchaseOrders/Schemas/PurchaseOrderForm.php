<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Currency;
use App\Models\RepairRequest;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('reference')
                ->maxLength(100),
            Select::make('vendor_id')
                ->label('Vendor')
                ->options(Vendor::pluck('name', 'id'))
                ->required(),
            Select::make('repair_request_id')
                ->label('Repair Request')
                ->options(RepairRequest::pluck('reference', 'id'))
                ->nullable(),
            DatePicker::make('order_date'),
            DatePicker::make('expected_date'),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'confirmed' => 'Confirmed',
                    'partially_received' => 'Partially Received',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
            Textarea::make('notes')
                ->rows(3),
            TextInput::make('total_amount')
                ->numeric()
                ->prefix('$'),
            Select::make('currency_id')
                ->label('Currency')
                ->options(Currency::pluck('name', 'id'))
                ->nullable(),
        ]);
    }
}
