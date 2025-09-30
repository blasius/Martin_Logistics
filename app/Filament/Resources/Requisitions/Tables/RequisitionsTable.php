<?php

namespace App\Filament\Resources\Requisitions\Tables;

use App\Models\Requisition;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequisitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('expenseType.name')->label('Expense Type'),
                TextColumn::make('amount')->money(fn ($record) => $record->currency->code ?? 'USD'),
                TextColumn::make('status')->badge(),
                TextColumn::make('requester_name'),
                TextColumn::make('requester_role'),
                TextColumn::make('created_at')->since(),
            ])
            ->actions([
                ViewAction::make(),

                // Finance / Manager Approval
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('approval_notes')
                            ->label('Approval Notes'),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approval_notes' => $data['approval_notes'] ?? null,
                            'assigned_finance_user_id' => auth()->id(), // finance user
                        ]);
                    }),

                // Cashier Processing
                Action::make('process')
                    ->label('Process Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->form([
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Bank Transfer',
                            ])
                            ->required(),

                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->required(),

                        FileUpload::make('proof_file')
                            ->label('Upload Proof (PDF/Image)')
                            ->directory('payment_proofs')
                            ->downloadable(),

                        FileUpload::make('signatures')
                            ->label('Signatures (Cashier & Requester)')
                            ->image()
                            ->multiple()
                            ->directory('signatures'),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        $record->update([
                            'status' => 'processed',
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'],
                            'proof_file' => $data['proof_file'] ?? null,
                            'signatures' => $data['signatures'] ?? null,
                            'assigned_cashier_user_id' => auth()->id(),
                            'voucher_number' => 'VCH-' . str_pad($record->id, 6, '0', STR_PAD_LEFT),
                        ]);
                    }),

            ]);
    }
}
