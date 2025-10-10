<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\RolePermissionAudit;
use Illuminate\Support\Facades\Auth;

class RolePermissionObserver
{
    public function created($model): void
    {
        $this->logChange('created', $model);
    }

    public function updated($model): void
    {
        $this->logChange('updated', $model);
    }

    public function deleted($model): void
    {
        $this->logChange('deleted', $model);
    }

    protected function logChange(string $action, $model): void
    {
        RolePermissionAudit::create([
            'admin_id' => Auth::id(),
            'action' => "{$action}_" . ($model instanceof Role ? 'role' : 'permission'),
            'target_type' => $model instanceof Role ? 'role' : 'permission',
            'target_id' => $model->id,
            'details' => [
                'name' => $model->name ?? null,
                'timestamp' => now()->toDateTimeString(),
            ],
        ]);

        \Log::info('Audit log fired for '.$action, ['model' => get_class($model)]);
    }
}
