<?php

namespace App\Filament\Pages;

use App\Models\RepairRequest;
use App\Models\Vehicle;
use App\Models\Part;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class WorkshopDashboard extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Workshop';
    protected static string|null|\UnitEnum $navigationGroup = 'Workshop';
    protected static ?string $slug = 'workshop-dashboard';
    protected string $view = 'filament.pages.workshop-dashboard';
    protected static ?string $title = 'Workshop Dashboard';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasAnyRole(['Super Admin', 'Admin', 'Operator', 'Mechanic', 'Logistics Manager']));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    public function getTotalInWorkshopProperty(): int
    {
        return RepairRequest::whereNotIn('status', ['released', 'cancelled'])->count();
    }

    public function getByStatusProperty(): array
    {
        $counts = RepairRequest::selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending_approval' => $counts['pending_approval'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'parts_pending' => $counts['parts_pending'] ?? 0,
            'in_progress' => $counts['in_progress'] ?? 0,
            'completed' => $counts['completed'] ?? 0,
        ];
    }

    public function getReleasedPoolProperty()
    {
        return Vehicle::where('status', 'released_from_workshop')->get();
    }

    public function getLowStockPartsProperty()
    {
        return \App\Models\StockLevel::with('part', 'warehouse')
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->where('min_quantity', '>', 0)
            ->get();
    }

    public function getRecentRequestsProperty()
    {
        return RepairRequest::with('vehicle', 'mechanic')
            ->latest()
            ->take(10)
            ->get();
    }
}
