<!-- resources/js/pages/ControlTower/Index.vue -->
<template>
    <div class="p-6 space-y-6">

        <!-- Page Title -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="lucide lucide-radar text-indigo-600"></i>
                Live Control Tower
            </h1>

            <button
                @click="refreshData"
                class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
            >
                Refresh
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="card in summary" :key="card.title"
                 class="p-4 bg-white shadow rounded border border-gray-100 flex items-center gap-4">
                <i :class="card.icon + ' text-3xl ' + card.color"></i>
                <div>
                    <p class="text-gray-500 text-sm">{{ card.title }}</p>
                    <p class="text-xl font-bold">{{ card.value }}</p>
                </div>
            </div>
        </div>

        <!-- Live Vehicles Table -->
        <div class="bg-white shadow rounded border border-gray-200 p-5">
            <h2 class="text-lg font-semibold mb-3">Vehicles</h2>

            <table class="w-full table-auto text-sm">
                <thead>
                <tr class="border-b">
                    <th class="py-2 text-left">Plate</th>
                    <th class="py-2 text-left">Status</th>
                    <th class="py-2 text-left">Speed</th>
                    <th class="py-2 text-left">Fuel</th>
                    <th class="py-2 text-left">Last Updated</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="v in vehicles" :key="v.id" class="border-b hover:bg-gray-50">
                    <td class="py-2">{{ v.plate }}</td>
                    <td class="py-2">
                            <span :class="statusBadge(v.status)">
                                {{ v.status }}
                            </span>
                    </td>
                    <td class="py-2">{{ v.speed }} km/h</td>
                    <td class="py-2">{{ v.fuel }}%</td>
                    <td class="py-2 text-gray-500">{{ v.updated }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Live Trailers Table -->
        <div class="bg-white shadow rounded border border-gray-200 p-5">
            <h2 class="text-lg font-semibold mb-3">Trailers</h2>

            <table class="w-full table-auto text-sm">
                <thead>
                <tr class="border-b">
                    <th class="py-2 text-left">Plate</th>
                    <th class="py-2 text-left">Status</th>
                    <th class="py-2 text-left">Location</th>
                    <th class="py-2 text-left">Last Updated</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="t in trailers" :key="t.id" class="border-b hover:bg-gray-50">
                    <td class="py-2">{{ t.plate }}</td>
                    <td class="py-2">
                            <span :class="statusBadge(t.status)">
                                {{ t.status }}
                            </span>
                    </td>
                    <td class="py-2">{{ t.location }}</td>
                    <td class="py-2 text-gray-500">{{ t.updated }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Trip Progress -->
        <div class="bg-white shadow rounded border border-gray-200 p-5">
            <h2 class="text-lg font-semibold mb-3">Active Trips</h2>

            <div class="space-y-4">
                <div v-for="trip in trips" :key="trip.id">
                    <p class="font-medium">
                        {{ trip.vehicle }} → <span class="text-gray-600">{{ trip.destination }}</span>
                    </p>
                    <div class="w-full bg-gray-200 rounded h-4 mt-1">
                        <div class="h-4 rounded"
                             :style="{ width: trip.progress + '%', backgroundColor: '#4f46e5' }">
                        </div>
                    </div>
                    <p class="text-gray-500 text-xs mt-1">{{ trip.progress }}% completed</p>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const summary = ref([
    { title: "Active Vehicles", value: 0, icon: "lucide-truck", color: "text-indigo-600" },
    { title: "Active Trailers", value: 0, icon: "lucide-trailer", color: "text-orange-600" },
    { title: "Active Trips", value: 0, icon: "lucide-route", color: "text-green-600" },
    { title: "New Fines Today", value: 0, icon: "lucide-alert-triangle", color: "text-red-600" },
]);

// Dummy data
const vehicles = ref([]);
const trailers = ref([]);
const trips = ref([]);

// Generates fake real-time values
function loadDummyData() {
    summary.value[0].value = 12;
    summary.value[1].value = 5;
    summary.value[2].value = 7;
    summary.value[3].value = Math.floor(Math.random() * 5);

    vehicles.value = [
        { id: 1, plate: "RAB 123A", status: randomStatus(), speed: randomInt(0,95), fuel: randomInt(30,100), updated: "1m ago" },
        { id: 2, plate: "RAD 442C", status: randomStatus(), speed: randomInt(0,95), fuel: randomInt(30,100), updated: "5m ago" },
        { id: 3, plate: "RAC 883Z", status: randomStatus(), speed: randomInt(0,95), fuel: randomInt(30,100), updated: "3m ago" },
    ];

    trailers.value = [
        { id: 1, plate: "TRL-44A", status: randomStatus(), location: "Gahanga", updated: "2m ago" },
        { id: 2, plate: "TRL-22K", status: randomStatus(), location: "Nyamata", updated: "4m ago" },
    ];

    trips.value = [
        { id: 1, vehicle: "RAB 123A", destination: "Goma", progress: randomInt(10,85) },
        { id: 2, vehicle: "RAC 883Z", destination: "Bujumbura", progress: randomInt(5,65) },
    ];
}

function randomStatus() {
    return ["Moving", "Stopped", "Delayed", "Offline"][Math.floor(Math.random() * 4)];
}

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function statusBadge(status) {
    return {
        Moving: "px-2 py-1 text-xs rounded bg-green-100 text-green-700",
        Stopped: "px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700",
        Delayed: "px-2 py-1 text-xs rounded bg-orange-100 text-orange-700",
        Offline: "px-2 py-1 text-xs rounded bg-gray-200 text-gray-600",
    }[status];
}

function refreshData() {
    loadDummyData();
}

onMounted(() => {
    loadDummyData();

    // auto-refresh every 10 seconds
    setInterval(() => {
        loadDummyData();
    }, 10000);
});
</script>

<style scoped>
.lucide {
    width: 24px;
    height: 24px;
}
</style>
