<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Fuel Management</h1>
                <p class="text-xs font-bold text-slate-400">Real-time tank telemetry &amp; consumption analytics</p>
            </div>
            <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50 shadow-sm">
                <RefreshCw class="w-4 h-4 text-slate-500" />
            </button>
        </div>

        <!-- Stats Grid -->
        <div v-if="loading" class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-24 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Total Capacity</p>
                <p class="text-3xl font-black text-slate-800 mt-1">{{ stats.total_capacity?.toLocaleString() || 0 }} L</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Current Stock</p>
                <p class="text-3xl font-black text-amber-500 mt-1">{{ stats.total_current?.toLocaleString() || 0 }} L</p>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ stats.overall_percent }}% Full</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Dispensed Today</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ (stats.dispensed_today || 0).toLocaleString() }} L</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">This Month</p>
                <p class="text-3xl font-black text-indigo-600 mt-1">{{ (stats.dispensed_this_month || 0).toLocaleString() }} L</p>
            </div>
        </div>

        <!-- Tanks Grid -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-black text-xs text-slate-500 uppercase tracking-wider">Active Fuel Tanks</h2>
                <button @click="showTankModal = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-xl font-black text-[10px] uppercase flex items-center gap-1.5 shadow-sm">
                    <Plus class="w-3.5 h-3.5" /> Add Tank
                </button>
            </div>

            <div v-if="!stats.tanks?.length" class="text-center py-12">
                <p class="text-slate-400 font-bold text-xs uppercase">No fuel tanks configured</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="tank in stats.tanks"
                    :key="tank.id"
                    @click="$router.push(`/fuel/tanks/${tank.id}`)"
                    class="border border-slate-200 rounded-2xl p-5 hover:border-amber-400 hover:shadow-lg cursor-pointer transition-all bg-white flex flex-col justify-between"
                >
                    <div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-black text-base text-slate-800">{{ tank.code }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ tank.fuel_type }}</p>
                            </div>
                            <Fuel class="w-5 h-5" :class="tank.is_low ? 'text-rose-500' : 'text-slate-300'" />
                        </div>

                        <div class="my-4">
                            <TankGauge :capacity="tank.capacity" :current-level="tank.current_level" :reorder-threshold="tank.reorder_threshold" :fuel-type="tank.fuel_type" />
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center text-xs font-bold border-t border-slate-100 pt-3">
                            <span class="text-slate-600">{{ tank.current_level?.toLocaleString() }} / {{ tank.capacity?.toLocaleString() }} L</span>
                            <span class="font-black text-sm" :class="tank.is_low ? 'text-rose-600' : 'text-amber-500'">{{ tank.percent }}%</span>
                        </div>
                        <div v-if="tank.is_low" class="mt-2 flex items-center gap-1 text-rose-600 text-[10px] font-black uppercase">
                            <TriangleAlert class="w-3.5 h-3.5" /> Low Fuel Warning
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fuelApi } from '../../api/fuel/dashboard';
import { fuelTankApi } from '../../api/fuel/tanks';
import TankGauge from '../../components/TankGauge.vue';
import { Plus, RefreshCw, Fuel, TriangleAlert } from 'lucide-vue-next';

const loading = ref(true);
const stats = ref({});
const showTankModal = ref(false);

async function load() {
    loading.value = true;
    try {
        const res = await fuelApi.dashboard();
        stats.value = res.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

onMounted(load);
</script>
