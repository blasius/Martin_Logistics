<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Fuel Management</h1>
                <p class="text-xs font-bold text-slate-400">Tanks, dispensing &amp; consumption analytics</p>
            </div>
            <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                <RefreshCw class="w-4 h-4 text-slate-500" />
            </button>
        </div>

        <div v-if="loading" class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" class="h-20 bg-white rounded-2xl animate-pulse border border-slate-100"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Total Capacity</p>
                <p class="text-3xl font-black text-slate-800 mt-1">{{ stats.total_capacity?.toLocaleString() || 0 }} L</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Current Stock</p>
                <p class="text-3xl font-black text-blue-600 mt-1">{{ stats.total_current?.toLocaleString() || 0 }} L</p>
                <p class="text-xs text-slate-400 mt-1">{{ stats.overall_percent }}% full</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">Dispensed Today</p>
                <p class="text-3xl font-black text-emerald-600 mt-1">{{ (stats.dispensed_today || 0).toLocaleString() }} L</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-black text-slate-400 uppercase">This Month</p>
                <p class="text-3xl font-black text-amber-600 mt-1">{{ (stats.dispensed_this_month || 0).toLocaleString() }} L</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-black text-xs text-slate-500 uppercase">Fuel Tanks</h2>
                <button @click="showTankModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase flex items-center gap-1">
                    <Plus class="w-3 h-3" /> Add Tank
                </button>
            </div>
            <div v-if="!stats.tanks?.length" class="text-center py-10">
                <p class="text-slate-400 font-bold text-xs uppercase">No tanks configured</p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="tank in stats.tanks" :key="tank.id" @click="$router.push(`/fuel/tanks/${tank.id}`)" class="border border-slate-200 rounded-2xl p-5 hover:shadow-md cursor-pointer transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-black text-sm text-slate-800">{{ tank.code }}</p>
                            <p class="text-[10px] font-bold text-slate-400">{{ tank.fuel_type }}</p>
                        </div>
                        <Fuel class="w-5 h-5" :class="tank.is_low ? 'text-rose-500' : 'text-slate-300'" />
                    </div>
                    <div class="mt-3 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="tank.is_low ? 'bg-rose-500' : 'bg-emerald-500'" :style="{ width: Math.min(tank.percent, 100) + '%' }"></div>
                    </div>
                    <div class="flex justify-between mt-2">
                        <span class="text-xs font-bold text-slate-600">{{ tank.current_level?.toLocaleString() }} / {{ tank.capacity?.toLocaleString() }} L</span>
                        <span class="text-[10px] font-black" :class="tank.is_low ? 'text-rose-600' : 'text-slate-400'">{{ tank.percent }}%</span>
                    </div>
                    <div v-if="tank.is_low" class="mt-2 flex items-center gap-1 text-rose-600 text-[10px] font-black">
                        <TriangleAlert class="w-3 h-3" /> LOW STOCK
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showTankModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showTankModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">New Fuel Tank</h3>
                    <button @click="showTankModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="createTank" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Code</label>
                            <input v-model="tankForm.code" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Fuel Type</label>
                            <select v-model="tankForm.fuel_type" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="diesel">Diesel</option>
                                <option value="petrol">Petrol</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Name</label>
                        <input v-model="tankForm.name" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Capacity (L)</label>
                            <input v-model.number="tankForm.capacity" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Current Level</label>
                            <input v-model.number="tankForm.current_level" type="number" min="0" step="0.01" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Reorder At</label>
                            <input v-model.number="tankForm.reorder_threshold" type="number" min="0" step="0.01" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="tankForm.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Create Tank</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fuelApi } from '../../api/fuel/dashboard';
import { fuelTankApi } from '../../api/fuel/tanks';
import { Plus, RefreshCw, X, Fuel, TriangleAlert } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const stats = ref({});
const showTankModal = ref(false);
const tankForm = ref({ code: '', name: '', capacity: 0, current_level: 0, fuel_type: 'diesel', reorder_threshold: null, notes: '' });

async function load() {
    loading.value = true;
    try {
        const res = await fuelApi.dashboard();
        stats.value = res.data;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function createTank() {
    try {
        await fuelTankApi.store(tankForm.value);
        showTankModal.value = false;
        tankForm.value = { code: '', name: '', capacity: 0, current_level: 0, fuel_type: 'diesel', reorder_threshold: null, notes: '' };
        await load();
    } catch (e) { console.error(e); }
}

onMounted(load);
</script>
