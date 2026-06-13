<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditTrail
{
    protected static function bootHasAuditTrail()
    {
        static::created(function ($model) {
            static::recordAudit($model, 'created', null, $model->getAuditableAttributes($model));
        });

        static::updated(function ($model) {
            $changed = $model->getDirty();
            $original = $model->getOriginal();

            $oldValues = [];
            $newValues = [];

            foreach ($changed as $key => $newValue) {
                if (in_array($key, $model->getAuditExcluded())) {
                    continue;
                }
                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $newValue;
            }

            if (!empty($newValues)) {
                static::recordAudit($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            static::recordAudit($model, 'deleted', $model->getAuditableAttributes($model), null);
        });
    }

    protected static function recordAudit($model, string $action, $oldValues, $newValues)
    {
        $user = Auth::user();

        $model->auditLogs()->create([
            'user_id'    => $user?->id,
            'action'     => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => static::buildDescription($model, $action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected function getAuditableAttributes($model): array
    {
        $excluded = $this->getAuditExcluded();
        return collect($model->getAttributes())
            ->except($excluded)
            ->toArray();
    }

    protected function getAuditExcluded(): array
    {
        return property_exists($this, 'auditExcluded')
            ? $this->auditExcluded
            : [
                'password',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ];
    }

    protected static function buildDescription($model, string $action): ?string
    {
        $label = method_exists($model, 'auditLabel')
            ? $model->auditLabel()
            : class_basename($model) . ' #' . $model->getKey();

        return match ($action) {
            'created' => "{$label} was created",
            'updated' => "{$label} was updated",
            'deleted' => "{$label} was deleted",
            default   => "{$label} was {$action}",
        };
    }
}
