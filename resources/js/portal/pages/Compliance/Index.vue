<template>
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Fleet Health Radar</h3>
            <span class="px-2 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-lg">Live</span>
        </div>

        <div class="flex items-end gap-4 mb-6">
            <div class="text-4xl font-black text-slate-900 leading-none">{{ stats.health_percentage }}%</div>
            <div class="text-[10px] font-bold text-slate-400 uppercase pb-1">Compliant Fleet</div>
        </div>

        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden mb-8">
            <div class="h-full transition-all duration-1000"
                 :class="stats.health_percentage < 90 ? 'bg-rose-500' : 'bg-emerald-500'"
                 :style="{ width: stats.health_percentage + '%' }"></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Insurance Issues</p>
                <p class="text-xl font-black" :class="stats.insurance_alerts > 0 ? 'text-rose-600' : 'text-slate-700'">
                    {{ stats.insurance_alerts }}
                </p>
            </div>
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Safety Issues</p>
                <p class="text-xl font-black" :class="stats.inspection_alerts > 0 ? 'text-rose-600' : 'text-slate-700'">
                    {{ stats.inspection_alerts }}
                </p>
            </div>
        </div>

        <div class="mt-6">
            <button @click="$router.push('/portal/compliance/insurances')"
                    class="w-full py-3 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all">
                Resolve Compliance Issues
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
    health_percentage: 0,
    insurance_alerts: 0,
    inspection_alerts: 0
});

onMounted(async () => {
    const { data } = await axios.get('/api/portal/compliance-summary');
    stats.value = data;
});
</script>
