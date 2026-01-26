<template>
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-50 rounded-full"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Global Compliance Radar</h3>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-black text-slate-400 uppercase">Live Sync</span>
                </div>
            </div>

            <div class="flex items-baseline gap-2 mb-2">
                <span class="text-5xl font-black tracking-tighter"
                      :class="stats.health_percentage < 80 ? 'text-rose-600' : 'text-slate-900'">
                    {{ stats.health_percentage }}%
                </span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Fleet Ready</span>
            </div>

            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mb-8">
                <div class="h-full transition-all duration-700 ease-out"
                     :class="stats.health_percentage < 90 ? 'bg-rose-500' : 'bg-emerald-500'"
                     :style="{ width: stats.health_percentage + '%' }"></div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Insurance</p>
                    <p class="text-lg font-black" :class="stats.alerts.insurance > 0 ? 'text-rose-600' : 'text-slate-700'">
                        {{ stats.alerts.insurance }}
                    </p>
                </div>
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Safety</p>
                    <p class="text-lg font-black" :class="stats.alerts.inspections > 0 ? 'text-rose-600' : 'text-slate-700'">
                        {{ stats.alerts.inspections }}
                    </p>
                </div>
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Fines</p>
                    <p class="text-lg font-black" :class="stats.alerts.fines > 0 ? 'text-rose-600' : 'text-slate-700'">
                        {{ stats.alerts.fines }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between bg-slate-900 rounded-2xl p-4 cursor-pointer hover:bg-slate-800 transition-all group"
                 @click="$router.push('/portal/compliance/insurances')">
                <div class="text-white">
                    <p class="text-[10px] font-black uppercase opacity-60">Grounded Units</p>
                    <p class="text-sm font-black">{{ stats.grounded_total }} Vehicles</p>
                </div>
                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20">
                    <ArrowRight class="w-4 h-4 text-white" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { ArrowRight } from 'lucide-vue-next';

const stats = ref({
    health_percentage: 0,
    grounded_total: 0,
    alerts: { insurance: 0, inspections: 0, fines: 0 }
});

onMounted(async () => {
    const { data } = await axios.get('/api/portal/compliance-summary');
    stats.value = data;
});
</script>
