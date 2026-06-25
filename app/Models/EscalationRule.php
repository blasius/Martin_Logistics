<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationRule extends Model
{
    protected $fillable = [
        'ticket_source',
        'level',
        'escalate_after_minutes',
        'assign_to_role',
    ];

    public static function getRuleFor(string $source, int $level): ?self
    {
        return static::where('ticket_source', $source)
            ->where('level', $level)
            ->first();
    }

    public static function nextLevelFor(string $source, int $currentLevel): ?self
    {
        return static::where('ticket_source', $source)
            ->where('level', $currentLevel + 1)
            ->first();
    }

    public static function seedDefaults(): void
    {
        $defaults = [
            ['ticket_source' => 'auto_route_deviation', 'level' => 1, 'escalate_after_minutes' => 120, 'assign_to_role' => 'Logistics Manager'],
            ['ticket_source' => 'auto_route_deviation', 'level' => 2, 'escalate_after_minutes' => 240, 'assign_to_role' => 'Director of Operations'],
            ['ticket_source' => 'auto_route_deviation', 'level' => 3, 'escalate_after_minutes' => 480, 'assign_to_role' => 'Managing Director'],
            ['ticket_source' => 'auto_delay',           'level' => 1, 'escalate_after_minutes' => 120, 'assign_to_role' => 'Logistics Manager'],
            ['ticket_source' => 'auto_delay',           'level' => 2, 'escalate_after_minutes' => 240, 'assign_to_role' => 'Director of Operations'],
            ['ticket_source' => 'auto_delay',           'level' => 3, 'escalate_after_minutes' => 480, 'assign_to_role' => 'Managing Director'],
            ['ticket_source' => 'auto_fuel_flag',       'level' => 1, 'escalate_after_minutes' => 180, 'assign_to_role' => 'Logistics Manager'],
            ['ticket_source' => 'auto_fuel_flag',       'level' => 2, 'escalate_after_minutes' => 360, 'assign_to_role' => 'Director of Operations'],
            ['ticket_source' => 'auto_fuel_flag',       'level' => 3, 'escalate_after_minutes' => 720, 'assign_to_role' => 'Managing Director'],
        ];

        foreach ($defaults as $rule) {
            static::firstOrCreate(
                ['ticket_source' => $rule['ticket_source'], 'level' => $rule['level']],
                $rule
            );
        }
    }
}
