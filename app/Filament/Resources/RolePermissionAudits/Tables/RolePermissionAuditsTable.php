<?php

namespace App\Filament\Resources\RolePermissionAudits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RolePermissionAuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('#'),

                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('target_type')
                    ->label('Target Type')
                    ->sortable(),

                TextColumn::make('target_id')
                    ->label('Target ID'),

                TextColumn::make('details.name')
                    ->label('Name Changed')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->sortable()
                    ->dateTime('Y-m-d H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('target_type')
                    ->options([
                        'role' => 'Role',
                        'permission' => 'Permission',
                    ]),
                SelectFilter::make('action')
                    ->options([
                        'created_role' => 'Created Role',
                        'updated_role' => 'Updated Role',
                        'deleted_role' => 'Deleted Role',
                        'created_permission' => 'Created Permission',
                        'updated_permission' => 'Updated Permission',
                        'deleted_permission' => 'Deleted Permission',
                    ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Optionally restrict this page to super_admins
        return auth()->user()?->hasRole('super_admin');
    }
}
