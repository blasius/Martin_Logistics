<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Vehicle Fines Monitor</h1>
        <p class="text-gray-500 mb-6">Automatically checks traffic fines for all registered vehicles.</p>

        <div class="space-y-4">
            <div
                v-for="v in vehicles"
                :key="v.id"
                class="bg-white shadow rounded-xl p-4 flex justify-between items-start border"
                :class="{
          'border-green-500': v.status === 'clear',
          'border-red-500': v.status === 'fined'
        }"
            >
                <div>
                    <div class="text-lg font-semibold">{{ v.plate_number }}</div>
                    <div v-if="v.status === 'clear'" class="text-green-600 flex items-center gap-2">
                        <span class="text-xl">✅</span> No fines detected
                    </div>

                    <div v-else-if="v.status === 'fined'" class="text-red-600">
                        <div class="font-medium">Total Fines: {{ v.total }} RWF</div>
                        <div v-for="fine in v.fines" :key="fine.ticketNumber" class="mt-2 border-t pt-2">
                            <p><strong>Date Issued:</strong> {{ fine.issuedAt }}</p>
                            <p><strong>Pay By:</strong> {{ fine.payBy }}</p>
                            <p><strong>Violations:</strong></p>
                            <ul class="list-disc pl-6 text-gray-700">
                                <li v-for="violation in fine.violations" :key="violation.violationName">
                                    {{ violation.violationName }} — {{ violation.fineAmount }} RWF
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-else class="text-gray-400">⏳ Checking...</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { api } from "@/plugins/axios.js";

const vehicles = ref([]);

const randomDelay = (min, max) => Math.floor(Math.random() * (max - min + 1) + min) * 1000;

const checkFines = async (vehicle) => {
    try {
        const response = await api.get(`/api/fines/check/${vehicle.plate_number}`);
        const data = response.data;

        // CASE 1: No fines → status = false & data = null
        if (data.status === false || data.data === null) {
            vehicle.status = "clear";
            vehicle.fines = [];
            vehicle.total = 0;
            return;
        }

        // CASE 2: Has fines
        const tickets = data?.data?.trafficFines ?? [];

        if (tickets.length > 0) {
            vehicle.status = "fined";
            vehicle.fines = tickets;
            vehicle.total = tickets.reduce(
                (sum, fine) => sum + parseFloat(fine.ticketAmount),
                0
            );
        } else {
            vehicle.status = "clear";
        }
    } catch (error) {
        vehicle.status = "error";
    }
};

const startChecking = async () => {
    for (const v of vehicles.value) {
        await new Promise((resolve) => setTimeout(resolve, randomDelay(20, 35)));
        checkFines(v);
    }
};

onMounted(async () => {
    const res = await api.get("/api/vehicles");
    vehicles.value = res.data.map((v) => ({ ...v, status: null, fines: [], total: 0 }));
    startChecking();
});
</script>

<style scoped>
li {
    line-height: 1.5;
}
</style>
