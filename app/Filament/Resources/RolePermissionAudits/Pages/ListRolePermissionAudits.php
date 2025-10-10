<?php

namespace App\Filament\Resources\RolePermissionAudits\Pages;

use App\Filament\Resources\RolePermissionAudits\RolePermissionAuditResource;
use Filament\Resources\Pages\ListRecords;

class ListRolePermissionAudits extends ListRecords
{
    protected static string $resource = RolePermissionAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
