<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logistics Dashboard (Dummy)</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<aside class="w-64 h-screen bg-white shadow-lg flex flex-col">
    <div class="p-4 text-xl font-bold text-indigo-600 border-b">
        Logistics Ops
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Dashboard</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Fleet</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Trips</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Drivers</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Fuel</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Maintenance</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Alerts</a>
        <a href="#" class="block p-2 rounded hover:bg-indigo-100">Reports</a>
    </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-6 overflow-y-auto">
    <!-- KPIs -->
    <div class="grid grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Total Fleet</div>
            <div class="text-2xl font-bold">120</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Active</div>
            <div class="text-2xl font-bold text-green-600">79</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Idle</div>
            <div class="text-2xl font-bold text-yellow-600">31</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">Fuel Consumed (Today)</div>
            <div class="text-2xl font-bold">2,350 L</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500">On-Time Deliveries</div>
            <div class="text-2xl font-bold text-blue-600">94%</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Fleet Activity</h3>
            <canvas id="fleetActivity"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Fuel vs Distance</h3>
            <canvas id="fuelDistance"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Delivery Timeliness</h3>
            <canvas id="timeliness"></canvas>
        </div>
    </div>

    <!-- Map + Alerts -->
    <div class="grid grid-cols-3 gap-6 mb-6">
        <div class="col-span-2 bg-white rounded-lg shadow p-4 h-80 flex items-center justify-center text-gray-500">
            [ Map Placeholder ]
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Active Alerts</h3>
            <ul class="space-y-2">
                <li class="p-2 bg-red-100 text-red-700 rounded">Overspeed - Truck #45</li>
                <li class="p-2 bg-yellow-100 text-yellow-700 rounded">Idling > 30min - Truck #12</li>
                <li class="p-2 bg-red-100 text-red-700 rounded">Fuel Drop - Truck #7</li>
            </ul>
        </div>
    </div>

    <!-- Tables -->
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Vehicles Needing Service</h3>
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b">
                    <th class="text-left p-2">Vehicle</th>
                    <th class="text-left p-2">Mileage</th>
                    <th class="text-left p-2">Due In</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="p-2">Truck #14</td>
                    <td class="p-2">145,000 km</td>
                    <td class="p-2 text-red-600">Overdue</td>
                </tr>
                <tr>
                    <td class="p-2">Truck #39</td>
                    <td class="p-2">98,200 km</td>
                    <td class="p-2">3 days</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Driver Leaderboard</h3>
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b">
                    <th class="text-left p-2">Driver</th>
                    <th class="text-left p-2">Overspeeds</th>
                    <th class="text-left p-2">Idle Time</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="p-2">John Doe</td>
                    <td class="p-2 text-red-600">5</td>
                    <td class="p-2">2h 15m</td>
                </tr>
                <tr>
                    <td class="p-2">Jane Smith</td>
                    <td class="p-2">0</td>
                    <td class="p-2 text-green-600">45m</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // Dummy charts
    new Chart(document.getElementById('fleetActivity'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Idle', 'Offline'],
            datasets: [{ data: [79, 31, 10], backgroundColor: ['#22c55e', '#eab308', '#9ca3af'] }]
        }
    });

    new Chart(document.getElementById('fuelDistance'), {
        type: 'bar',
        data: {
            labels: ['Mon','Tue','Wed','Thu','Fri'],
            datasets: [
                { label: 'Fuel (L)', data: [2100,2200,2500,2300,2350] },
                { label: 'Distance (km)', data: [1200,1300,1400,1500,1550] }
            ]
        }
    });

    new Chart(document.getElementById('timeliness'), {
        type: 'line',
        data: {
            labels: ['Mon','Tue','Wed','Thu','Fri'],
            datasets: [{ label: 'On-time %', data: [92,93,90,95,94], borderColor: '#3b82f6' }]
        }
    });
</script>
</body>
</html>
