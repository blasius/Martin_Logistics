<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trip Expenses</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-md h-screen p-4">
    <h2 class="text-2xl font-bold mb-6">FleetView</h2>
    <nav class="space-y-2">
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200">Dashboard</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200">Trips</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200 bg-gray-200 font-semibold">Trip Expenses</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200">Vehicles</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200">Drivers</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-gray-200">Reports</a>
    </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-6">

    <!-- Trip Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-2">Trip #1245 – Kigali → Dar es Salaam</h2>
        <p class="text-gray-600">Vehicle: HOWO RAE 452D | Driver: John Doe</p>
        <p class="text-gray-600">Planned: 2025-09-18 08:00 | Actual: 2025-09-20 13:55</p>
        <p class="text-sm mt-2 text-gray-500">Status: <span class="text-yellow-600 font-semibold">Delayed</span></p>
    </div>

    <!-- Record Expense Form (dummy UI only) -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-bold mb-4">Record New Expense</h3>
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium">Type</label>
                <select class="w-full border rounded p-2">
                    <option>Fuel</option>
                    <option>Tolls</option>
                    <option>Per Diem</option>
                    <option>Repair</option>
                    <option>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Amount</label>
                <input type="number" class="w-full border rounded p-2" placeholder="0.00">
            </div>
            <div>
                <label class="block text-sm font-medium">Currency</label>
                <select class="w-full border rounded p-2">
                    <option>USD</option>
                    <option>RWF</option>
                    <option>TZS</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" class="bg-blue-600 text-white px-4 py-2 rounded">Add</button>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-bold mb-4">Recorded Expenses</h3>
        <table class="w-full border-collapse">
            <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-2">Type</th>
                <th class="p-2">Amount</th>
                <th class="p-2">Currency</th>
                <th class="p-2">Method</th>
                <th class="p-2">Recorded By</th>
            </tr>
            </thead>
            <tbody>
            <tr class="border-t">
                <td class="p-2">Fuel</td>
                <td class="p-2">$1,350</td>
                <td class="p-2">USD</td>
                <td class="p-2">Card</td>
                <td class="p-2">Dispatcher A</td>
            </tr>
            <tr class="border-t">
                <td class="p-2">Tolls</td>
                <td class="p-2">$220</td>
                <td class="p-2">USD</td>
                <td class="p-2">Cash</td>
                <td class="p-2">Driver</td>
            </tr>
            <tr class="border-t">
                <td class="p-2">Per Diem</td>
                <td class="p-2">$150</td>
                <td class="p-2">USD</td>
                <td class="p-2">Cash</td>
                <td class="p-2">Dispatcher B</td>
            </tr>
            <tr class="border-t bg-red-50">
                <td class="p-2">Port Charges</td>
                <td class="p-2">$80</td>
                <td class="p-2">USD</td>
                <td class="p-2">Cash</td>
                <td class="p-2">Driver</td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Totals & Variance -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded shadow text-center">
            <h4 class="text-sm text-gray-500">Planned Cost</h4>
            <p class="text-xl font-bold">$1,600</p>
        </div>
        <div class="bg-white p-4 rounded shadow text-center">
            <h4 class="text-sm text-gray-500">Actual Cost</h4>
            <p class="text-xl font-bold text-red-600">$1,800</p>
        </div>
        <div class="bg-white p-4 rounded shadow text-center">
            <h4 class="text-sm text-gray-500">Variance</h4>
            <p class="text-xl font-bold text-red-700">+ $200</p>
        </div>
    </div>

</main>
</body>
</html>
