<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\DriverVehicleAssignment;

class DashboardNumbers extends BaseWidget
{
    protected ?string $heading = 'KPIs';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All users')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Drivers', User::role('Driver')->count())
                ->description('Total drivers')
                ->color('success')
                ->icon('heroicon-o-user'),

            Stat::make('Vehicles', Vehicle::count())
                ->description('Total vehicles')
                ->color('secondary')
                ->icon('heroicon-o-truck'),

            Stat::make('Assignments', DriverVehicleAssignment::whereNull('end_date')->count())
                ->description('Ongoing assignments')
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
