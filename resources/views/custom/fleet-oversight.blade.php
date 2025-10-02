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

        <!-- Page title + date filter -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold">Fleet Overview</h2>
            <form class="flex items-center space-x-2">
                <input type="date" class="border rounded px-3 py-1" value="{{ date('Y-m-d') }}">
                <span>-</span>
                <input type="date" class="border rounded px-3 py-1" value="{{ date('Y-m-d') }}">
                <button class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Apply</button>
            </form>
        </div>

        @php
            // Dummy data
            $totalVehicles = 79;
            $vehiclesOnTrip = 45;
            $utilizationPercent = round(($vehiclesOnTrip / $totalVehicles) * 100);
        @endphp

        <!-- Metrics cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm text-gray-500">Utilization</h3>
                <p class="text-2xl font-bold">{{ $vehiclesOnTrip }}/{{ $totalVehicles }}({{ $utilizationPercent }}%)</p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm text-gray-500">Active Trips</h3>
                <p class="text-3xl font-bold">37</p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm text-gray-500">Deliveries On Time</h3>
                <p class="text-3xl font-bold text-green-600">92%</p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-sm text-gray-500">Fuel Costs</h3>
                <p class="text-3xl font-bold">RWf48,200</p>
            </div>
        </div>

        <div class="p-6 bg-gray-50 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-6">Current vehicle location by region</h2>

            @php
                // Group vehicles by region
                $regions = collect($vehicles)->groupBy('region')->sortByDesc(fn($group) => $group->count());
                $maxCount = $regions->isNotEmpty() ? $regions->max(fn($group) => $group->count()) : 1;
            @endphp

            <div class="space-y-4">
                @foreach ($regions as $region => $group)
                    @php
                        $count = $group->count();
                        $moving = $group->where('speed', '>', 0)->count();
                        $stopped = $count - $moving;

                        $color = match(true) {
                            $count >= 30 => 'bg-red-200 text-red-800',
                            $count >= 15 => 'bg-yellow-200 text-yellow-800',
                            default => 'bg-green-200 text-green-800',
                        };
                    @endphp

                    <div x-data="{ open: false }" class="border rounded-lg bg-white shadow-sm">
                        <!-- Summary row -->
                        <div @click="open = !open"
                             class="flex items-center gap-4 cursor-pointer px-4 py-3 hover:bg-gray-100 rounded-t-lg">

                            <!-- Region badge -->
                            <span class="px-3 py-1 rounded-full font-semibold {{ $color }} w-40 text-right">
                        {{ $region }}
                    </span>

                            <!-- Horizontal bar -->
                            <div class="flex-1 bg-gray-200 rounded h-6 relative">
                                <div class="bg-blue-500 h-6 rounded transition-all duration-500"
                                     style="width: {{ ($count / $maxCount) * 100 }}%">
                                </div>
                                <span class="absolute right-2 top-0 h-6 flex items-center text-sm font-semibold text-gray-700">
                            {{ $count }}
                        </span>
                            </div>

                            <!-- Moving / stopped counters -->
                            <div class="flex items-center gap-3 text-sm font-semibold">
                        <span class="px-2 py-1 rounded bg-green-100 text-green-800">
                            🟢 {{ $moving }}
                        </span>
                                <span class="px-2 py-1 rounded bg-red-100 text-red-800">
                            🔴 {{ $stopped }}
                        </span>
                            </div>

                            <span class="text-sm text-gray-500 ml-2" x-text="open ? '▼' : '▶'"></span>
                        </div>

                        <!-- Expanded vehicle list -->
                        <div x-show="open" x-collapse class="px-6 py-4 bg-gray-50 space-y-2">
                            @foreach ($group as $vehicle)
                                <div class="flex items-center justify-between p-3 bg-white rounded shadow-sm">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $vehicle['name'] }}</p>
                                        <p class="text-sm text-gray-500">Last seen: {{ \Carbon\Carbon::parse($vehicle['last_seen'])->diffForHumans() }}</p>
                                    </div>
                                    <div>
                                        @if ($vehicle['speed'] > 0)
                                            <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-sm font-semibold">
                                        🟢 Moving
                                    </span>
                                        @else
                                            <span class="px-2 py-1 rounded bg-red-100 text-red-800 text-sm font-semibold">
                                        🔴 Stopped
                                    </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>


        <!-- Charts section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white shadow rounded-lg p-6 h-80">
                <h3 class="text-lg font-semibold mb-4">Trips by Region</h3>
                <canvas id="tripsByRegion" class="w-full h-full"></canvas>
            </div>

            <div class="bg-white shadow rounded-lg p-6 h-80">
                <h3 class="text-lg font-semibold mb-4">Expenses Breakdown</h3>
                <canvas id="expensesBreakdown" class="w-full h-full"></canvas>
            </div>

            <div class="bg-white shadow rounded-lg p-6 h-80">
                <h3 class="text-lg font-semibold mb-4">Fleet Utilization</h3>
                <canvas id="fleetUtilization" class="w-full h-full"></canvas>
            </div>

            <div class="bg-white shadow rounded-lg p-6 h-80">
                <h3 class="text-lg font-semibold mb-4">Trips On Time vs Delayed</h3>
                <canvas id="tripsOnTime" class="w-full h-full"></canvas>
            </div>
        </div>

    </main>
</div>

<script>
    // Dummy chart data
    const tripsByRegion = new Chart(document.getElementById('tripsByRegion'), {
        type: 'bar',
        data: {
            labels: ['Kigali', 'Nairobi', 'Dar es Salaam', 'Kampala'],
            datasets: [{
                label: 'Trips',
                data: [12, 19, 7, 15],
                backgroundColor: '#3B82F6'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const expensesBreakdown = new Chart(document.getElementById('expensesBreakdown'), {
        type: 'doughnut',
        data: {
            labels: ['Fuel', 'Repairs', 'Fines', 'Other'],
            datasets: [{
                data: [48, 25, 10, 17],
                backgroundColor: ['#3B82F6','#EF4444','#F59E0B','#10B981']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const fleetUtilization = new Chart(document.getElementById('fleetUtilization'), {
        type: 'line',
        data: {
            labels: ['Week 1','Week 2','Week 3','Week 4'],
            datasets: [{
                label: 'Utilization %',
                data: [70, 75, 80, 78],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246,0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    const tripsOnTime = new Chart(document.getElementById('tripsOnTime'), {
        type: 'pie',
        data: {
            labels: ['On Time', 'Delayed'],
            datasets: [{
                data: [92, 8],
                backgroundColor: ['#10B981', '#EF4444']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>

</body>
</html>
