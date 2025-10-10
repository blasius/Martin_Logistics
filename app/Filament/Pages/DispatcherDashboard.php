<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Trip;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class DispatcherDashboard extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'My Dashboard';
    protected static ?string $slug = 'dispatcher-dashboard';
    protected string $view = 'filament.pages.dispatcher-dashboard';
    protected static ?string $title = 'Dispatcher Dashboard';

    // Restrict who can see this page
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Dispatcher', 'Operator', 'Admin', 'Super Admin']);
    }

    // Optional header actions (e.g., refresh)
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    public function getActiveTripsProperty()
    {
        return Trip::where('status', '!=', 'completed')
            ->where('created_by', Auth::id())
            ->latest()
            ->take(5)
            ->get();
    }
}
