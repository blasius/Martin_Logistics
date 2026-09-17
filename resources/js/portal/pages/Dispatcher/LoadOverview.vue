<template>
    <div class="p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 uppercase">Dispatcher Load</h1>
                <p class="text-xs font-medium text-slate-400 mt-1">Equal distribution & vehicle ownership per dispatcher</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs text-slate-500 font-bold">Period Days</label>
                <input v-model="days" type="number" min="1" max="365" class="border rounded px-3 py-2 text-sm w-20" />
                <button @click="load" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold">Refresh</button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8 text-slate-400">Loading...</div>

        <div v-else>
            <div v-if="dispatchers.length === 0" class="text-center py-16 bg-white rounded-xl shadow border border-slate-100 text-slate-400">
                No dispatchers found.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="dp in dispatchers" :key="dp.id" class="bg-white rounded-xl shadow border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">{{ dp.name }}</div>
                            <div class="text-xs text-slate-400">{{ dp.email }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-black" :class="loadColor(dp.active_trips)">{{ dp.active_trips }}</div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Active Trips</div>
                        </div>
                    </div>

                    <div class="px-5 py-3 grid grid-cols-3 gap-3 text-center">
                        <div>
                            <div class="text-lg font-black text-slate-800">{{ dp.trips_managed }}</div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Managed</div>
                        </div>
                        <div>
                            <div class="text-lg font-black" :class="scoreColor(dp.on_time_rate)">{{ dp.on_time_rate }}%</div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">On-Time</div>
                        </div>
                        <div>
                            <div class="text-lg font-black text-slate-800">{{ dp.vehicles_owned }}</div>
                            <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Vehicles</div>
                        </div>
                    </div>

                    <div v-if="dp.vehicles?.length" class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                        <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black mb-1.5">Owned Vehicles</div>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="v in dp.vehicles" :key="v.id"
                                class="text-[10px] font-bold text-slate-600 bg-white border border-slate-200 rounded px-1.5 py-0.5">
                                {{ v.plate_number }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { dispatchPrepApi } from '../../api/truck-requests'

const dispatchers = ref([])
const loading = ref(false)
const days = ref(30)

function loadColor(count) {
    if (count >= 10) return 'text-red-600'
    if (count >= 5) return 'text-amber-600'
    return 'text-emerald-600'
}

function scoreColor(val) {
    if (val >= 80) return 'text-green-600'
    if (val >= 60) return 'text-yellow-600'
    return 'text-red-600'
}

async function load() {
    loading.value = true
    try {
        const res = await dispatchPrepApi.loadOverview(days.value)
        dispatchers.value = res.data.data || []
    } catch (e) {
        console.error('Failed to load dispatcher overview', e)
    } finally {
        loading.value = false
    }
}

onMounted(load)
</script>