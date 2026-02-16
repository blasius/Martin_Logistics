<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-8 py-6 flex items-center justify-between shadow-sm z-10">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tighter uppercase leading-none">Compliance Radar</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Unified Truck & Trailer Health</p>
            </div>
            <div class="flex gap-2">
                <button @click="loadData" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><RefreshCw class="w-4 h-4 text-slate-400" /></button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-4">Fleet Health</p>
                    <p class="text-5xl font-black" :class="data.stats.health_percentage < 80 ? 'text-rose-600' : 'text-slate-900'">{{ data.stats.health_percentage }}%</p>
                </div>
                <div class="bg-rose-600 p-6 rounded-3xl shadow-xl shadow-rose-100">
                    <p class="text-[10px] font-black text-rose-100 uppercase mb-4">Grounded</p>
                    <p class="text-5xl font-black text-white">{{ data.stats.grounded_count }}</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-4">Ins. Alerts</p>
                    <p class="text-5xl font-black text-slate-900">{{ data.stats.insurance_alerts }}</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-4">Fine Alerts</p>
                    <p class="text-5xl font-black text-slate-900">{{ data.stats.fine_alerts }}</p>
                </div>
            </div>

            <section class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-xs font-black text-slate-900 uppercase">Grounded Analysis</h3>
                    <div class="flex bg-slate-200 p-1 rounded-lg gap-1">
                        <button v-for="t in ['all', 'Truck', 'Trailer']" :key="t" @click="filter = t"
                                :class="filter === t ? 'bg-white shadow-sm' : 'text-slate-500'"
                                class="px-3 py-1 text-[9px] font-black uppercase rounded-md transition-all">{{ t }}</button>
                    </div>
                </div>
                <table class="w-full">
                    <thead class="bg-slate-50/50 text-left">
                    <tr>
                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase">Unit Plate</th>
                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase">Type</th>
                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase">Issues</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <tr v-for="unit in filteredUnits" :key="unit.plate" class="hover:bg-slate-50/50">
                        <td class="px-8 py-5 font-black text-slate-900 uppercase text-lg">{{ unit.plate }}</td>
                        <td class="px-8 py-5">
                            <span class="px-2 py-1 bg-slate-100 rounded text-[9px] font-black uppercase">{{ unit.type }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex gap-2">
                                <span v-if="unit.issues.insurance" class="px-2 py-1 bg-rose-100 text-rose-600 text-[8px] font-black rounded uppercase">Insurance</span>
                                <span v-if="unit.issues.inspection" class="px-2 py-1 bg-amber-100 text-amber-600 text-[8px] font-black rounded uppercase">Inspection</span>
                                <span v-if="unit.issues.fines" class="px-2 py-1 bg-orange-100 text-orange-600 text-[8px] font-black rounded uppercase">Overdue Fine</span>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { api } from "../../../plugins/axios";
import { RefreshCw } from 'lucide-vue-next';

const filter = ref('all');
const data = reactive({
    stats: { health_percentage: 0, grounded_count: 0, insurance_alerts: 0, fine_alerts: 0 },
    grounded_list: []
});

const filteredUnits = computed(() => {
    if (filter.value === 'all') return data.grounded_list;
    return data.grounded_list.filter(u => u.type === filter.value);
});

const loadData = async () => {
    const res = await axios.get('/portal/compliance-summary');
    Object.assign(data, res.data);
};

onMounted(loadData);
</script>
