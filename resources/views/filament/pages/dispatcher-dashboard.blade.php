<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fleet Overview</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">

    <!-- Main content -->
    <main class="flex-1 p-6">

        <div class="space-y-6">

            {{-- Header --}}
            <div class="p-4 bg-white rounded-2xl shadow">
                <h2 class="text-2xl font-bold text-gray-800">👋 Welcome back, {{ auth()->user()->name }}!</h2>
                <p class="text-sm text-gray-500">Role: {{ auth()->user()->roles->pluck('name')->implode(', ') }}</p>
            </div>

            {{-- Active Trips --}}
            <div class="p-4 bg-white rounded-2xl shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">🚛 My Active Trips</h3>

                @forelse($this->activeTrips as $trip)
                    <div class="border rounded-xl p-3 mb-3 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-800">Trip #{{ $trip->id }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ $trip->origin }} → {{ $trip->destination }}
                                </p>
                                <p class="text-sm text-gray-500">Driver: {{ $trip->driver->name ?? 'N/A' }}</p>
                            </div>
                            <div class="text-right">
                            <span class="px-3 py-1 text-xs rounded-full
                                {{ $trip->status === 'en_route' ? 'bg-yellow-100 text-yellow-700' :
                                   ($trip->status === 'returning' ? 'bg-blue-100 text-blue-700' :
                                   ($trip->status === 'loaded' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600')) }}">
                                {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                            </span>
                            </div>
                        </div>

                        {{-- Timeline --}}
                        <div class="flex items-center mt-3 space-x-2">
                            @foreach (['loaded', 'en_route', 'returning', 'offloaded'] as $step)
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full
                                    {{ $trip->status === $step ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                    @if (!$loop->last)
                                        <div class="w-10 h-0.5 bg-gray-300"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-3 flex space-x-2">
                            @can('update trips')
                                <x-filament::button size="sm" color="primary">
                                    Update Status
                                </x-filament::button>
                            @endcan
                            @can('approve expenses')
                                <x-filament::button size="sm" color="success">
                                    Approve Expense
                                </x-filament::button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No active trips right now.</p>
                @endforelse
            </div>

            {{-- Pending Approvals --}}
            <div class="p-4 bg-white rounded-2xl shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">💰 Pending Expense Approvals</h3>
                    <p class="text-sm text-gray-500">No pending approvals.</p>
            </div>

        </div>

    </main>
</div>


</body>
</html>
