<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AccessReviewNotification;
use App\Services\AccessReviewService;
use Illuminate\Console\Command;

class ReviewAccess extends Command
{
    protected $signature = 'access:review {--force : Lock eligible accounts even when auto-deactivation is disabled}';
    protected $description = 'Review accounts for inactivity, integrity issues and permission hygiene';

    public function handle(AccessReviewService $service): int
    {
        $review = $service->review();
        $summary = $review['summary'];
        $findings = $review['findings'];

        $this->newLine();
        $this->info('Access Review Summary');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total users', $summary['total_users']],
                ['Users with roles', $summary['with_roles']],
                ['Flagged accounts', $summary['flagged']],
                ['Inactive users', $summary['inactive_users']],
                ['Idle dispatchers', $summary['idle_dispatchers']],
                ['Drivers without vehicle', $summary['drivers_without_vehicle']],
                ['Inactive mechanics', $summary['mechanics_inactive']],
                ['Orphan role assignments', $summary['orphan_roles']],
                ['Locked accounts', $summary['locked']],
            ]
        );

        if (empty($findings)) {
            $this->newLine();
            $this->info('No access findings — all clear.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Findings');
        $this->newLine();

        $this->table(
            ['Category', 'Severity', 'User', 'Roles', 'Detail'],
            collect($findings)->map(fn ($f) => [
                str_replace('_', ' ', $f['category']),
                ucfirst($f['severity']),
                "{$f['name']} <{$f['email']}>",
                implode(', ', $f['roles']),
                $f['detail'],
            ])
        );

        // ── Auto-deactivation (config-gated, or forced) ──
        $autoEnabled = (bool) config('access_review.auto_deactivate');
        $force = (bool) $this->option('force');

        if ($autoEnabled || $force) {
            $locked = $service->autoDeactivate($review);
            if ($locked > 0) {
                $this->warn("Auto-deactivation locked {$locked} account(s).");
            } else {
                $this->info('No accounts were eligible for auto-deactivation.');
            }
        } else {
            $this->newLine();
            $this->comment('Auto-deactivation is disabled. Enable via ACCESS_REVIEW_AUTO_DEACTIVATE=true or pass --force to lock eligible accounts now.');
        }

        // ── Notify managers ──
        $managerRoles = collect(['super_admin', 'Admin', 'Director of Operations', 'Logistics Manager', 'Operations Manager'])
            ->filter(fn ($role) => \App\Models\Role::where('name', $role)->exists())
            ->values()
            ->all();

        $managers = $managerRoles
            ? User::role($managerRoles)->whereNull('locked_at')->get()
            : collect();

        foreach ($managers as $manager) {
            $manager->notify(new AccessReviewNotification($summary));
        }

        if ($managers->isNotEmpty()) {
            $this->line("Notified " . $managers->count() . " manager(s).");
        }

        return Command::SUCCESS;
    }
}