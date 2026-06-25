<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole {
    protected $fillable = [
        'name', 'guard_name', 'slug', 'description',
        'is_super_admin', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

