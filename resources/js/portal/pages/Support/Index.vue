<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3 tracking-tighter uppercase">
                    <LifeBuoy class="text-indigo-600 w-8 h-8" /> Support Command
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Live Preview Mode</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                @click="activeCategoryId = null"
                :class="[!activeCategoryId ? 'bg-slate-900 text-white shadow-xl translate-y-[-2px]' : 'bg-white text-slate-500 border-slate-200 hover:border-indigo-300']"
                class="px-6 py-3 rounded-2xl border font-black text-xs uppercase transition-all flex items-center gap-2">
                All Issues <span class="opacity-50 text-[10px]">{{ allTickets.length }}</span>
            </button>

            <template v-for="cat in dynamicCategoryStats" :key="cat.id">
                <button
                    @click="activeCategoryId = cat.id"
                    :class="[activeCategoryId === cat.id ? 'bg-indigo-600 text-white shadow-xl translate-y-[-2px] border-indigo-600' : 'bg-white text-slate-700 border-slate-200 shadow-sm hover:border-indigo-300']"
                    class="px-6 py-3 rounded-2xl border font-black text-xs uppercase transition-all flex items-center gap-3">
                    {{ cat.name }}
                    <span :class="activeCategoryId === cat.id ? 'bg-white text-indigo-600' : 'bg-rose-500 text-white'" class="px-2 py-0.5 rounded-lg text-[10px]">
                        {{ cat.count }}
                    </span>
                </button>
            </template>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                <tr>
                    <th class="p-5">Reference</th>
                    <th class="p-5">Driver & Vehicle</th>
                    <th class="p-5">Issue</th>
                    <th class="p-5">Status</th>
                    <th class="p-5 text-right">Activity</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <tr v-for="t in filteredTickets" :key="t.id" @click="openTicket(t)" class="hover:bg-indigo-50/20 transition-colors cursor-pointer group">
                    <td class="p-5">
                        <span class="text-xs font-black text-slate-900 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">#{{ t.reference }}</span>
                    </td>
                    <td class="p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-xs">
                                {{ t.driver.substring(0,2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm tracking-tight">{{ t.driver }}</p>
                                <p class="text-[10px] font-bold text-indigo-600 uppercase">{{ t.subject.plate }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5">
                        <p class="text-sm font-bold text-slate-700 leading-snug">{{ t.title }}</p>
                        <p class="text-[10px] font-black text-slate-400 mt-1 uppercase">{{ t.category_name }}</p>
                    </td>
                    <td class="p-5">
                            <span :class="t.priority === 'urgent' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'" class="text-[9px] font-black px-2.5 py-1 rounded-lg uppercase tracking-widest">
                                {{ t.priority }}
                            </span>
                    </td>
                    <td class="p-5 text-right">
                        <p class="text-xs font-black text-slate-600">{{ t.timeAgo }}</p>
                        <span class="text-[9px] font-bold text-slate-400 uppercase italic">Pending Dispatch</span>
                    </td>
                </tr>
                <tr v-if="filteredTickets.length === 0">
                    <td colspan="5" class="p-20 text-center text-slate-400 font-black uppercase tracking-widest text-xs">
                        No active issues in this category
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Transition name="slide">
            <div v-if="activeTicket" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="activeTicket = null"></div>
                <div class="relative bg-white w-full max-w-4xl h-full shadow-2xl flex border-l-8 border-indigo-600">

                    <aside class="w-80 border-r border-slate-100 p-8 bg-slate-50/50 overflow-y-auto">
                        <div class="space-y-8">
                            <div>
                                <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-4">Trip Intelligence</h3>
                                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Progress</span>
                                        <span class="text-xs font-black text-slate-800">{{ activeTicket.subject.progress }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div :style="{width: activeTicket.subject.progress + '%'}" class="bg-indigo-600 h-full rounded-full"></div>
                                    </div>
                                    <div class="mt-4 flex justify-between text-[9px] font-black uppercase text-slate-400">
                                        <span>{{ activeTicket.subject.origin }}</span>
                                        <span>{{ activeTicket.subject.dest }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Telemetry</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                                        <p class="text-[8px] font-black text-slate-400 uppercase">Fuel</p>
                                        <p class="text-sm font-black text-slate-800">{{ activeTicket.subject.fuel }}%</p>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                                        <p class="text-[8px] font-black text-slate-400 uppercase">Temp</p>
                                        <p class="text-sm font-black text-emerald-600">Optimal</p>
                                    </div>
                                </div>
                            </div>

                            <button @click="markResolved(activeTicket.id)" class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-[10px] uppercase shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">
                                Mark Resolved
                            </button>
                        </div>
                    </aside>

                    <div class="flex-1 flex flex-col min-w-0">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                            <div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">{{ activeTicket.title }}</h2>
                                <p class="text-[10px] font-black text-slate-400 uppercase">{{ activeTicket.driver }} • {{ activeTicket.subject.plate }}</p>
                            </div>
                            <button @click="activeTicket = null" class="p-2 bg-slate-50 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-colors">
                                <X class="w-6 h-6" />
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-slate-50/30">
                            <div v-for="(msg, idx) in activeTicket.messages" :key="idx"
                                 :class="['flex flex-col max-w-[85%]', msg.is_me ? 'ml-auto items-end' : 'items-start']">
                                <div :class="[
                                    'p-4 rounded-3xl text-sm font-medium shadow-sm',
                                    msg.is_me ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700'
                                ]">
                                    {{ msg.text }}
                                </div>
                                <span class="text-[9px] font-black text-slate-400 uppercase mt-2 px-2">{{ msg.time }}</span>
                            </div>
                        </div>

                        <div class="p-6 bg-white border-t border-slate-100">
                            <div class="relative">
                                <textarea v-model="replyText" placeholder="Type message..." class="w-full bg-slate-50 border-none rounded-3xl p-5 pr-16 text-sm focus:ring-1 focus:ring-indigo-500 min-h-[80px] font-medium"></textarea>
                                <button @click="sendReply" class="absolute right-4 bottom-4 p-3 bg-indigo-600 text-white rounded-2xl shadow-xl">
                                    <Send class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { LifeBuoy, X, Send, Fuel, CheckCircle } from 'lucide-vue-next';

const activeCategoryId = ref(null);
const activeTicket = ref(null);
const replyText = ref('');

// --- DUMMY SOURCE OF TRUTH ---
// If you want a category badge to show 5, you must have 5 tickets here with that category_id.
const allTickets = ref([
    {
        id: 1, reference: 'FL-991', category_id: 1, category_name: 'Fuel Issues', title: 'Fuel Card PIN Lock', priority: 'urgent', timeAgo: '2m ago',
        driver: 'Munyagisenyi Moses',
        subject: { plate: 'RAH476E/RL5949', progress: 65, fuel: 12, origin: 'Kigali', dest: 'MBARARA' },
        messages: [
            { is_me: false, text: 'The card is locked after 3 attempts.', time: '10:00 AM' },
            { is_me: true, text: 'Resetting the PIN now. Check app in 1m.', time: '10:05 AM' }
        ]
    },
    {
        id: 2, reference: 'FL-992', category_id: 1, category_name: 'Fuel Issues', title: 'Station doesn\'t accept card', priority: 'normal', timeAgo: '15m ago',
        driver: 'IRARISHIKIJE J. CLAUDE',
        subject: { plate: 'RAI233W/RL3006', progress: 20, fuel: 5, origin: 'Kigali', dest: 'Kampala' },
        messages: []
    },
    {
        id: 3, reference: 'ME-101', category_id: 2, category_name: 'Breakdowns', title: 'Flat Tyre - Route A2', priority: 'urgent', timeAgo: '1h ago',
        driver: 'KAVUTSE EMMANUEL',
        subject: { plate: 'RAH602E/RL5948', progress: 85, fuel: 40, origin: 'Mombasa', dest: 'Kigali' },
        messages: []
    }
]);

// --- DYNAMIC COMPUTATION ---
// This handles your requirement: counts reflect actual data + hidden if 0.
const dynamicCategoryStats = computed(() => {
    const stats = [
        { id: 1, name: 'Fuel Issues' },
        { id: 2, name: 'Breakdowns' },
        { id: 3, name: 'Customs' },
        { id: 4, name: 'Safety' }
    ];

    return stats.map(cat => ({
        ...cat,
        count: allTickets.value.filter(t => t.category_id === cat.id).length
    })).filter(cat => cat.count > 0); // ONLY SHOW IF COUNT > 0
});

const filteredTickets = computed(() => {
    if (!activeCategoryId.value) return allTickets.value;
    return allTickets.value.filter(t => t.category_id === activeCategoryId.value);
});

// --- ACTIONS ---
const openTicket = (t) => activeTicket.value = t;

const sendReply = () => {
    if (!replyText.value) return;
    activeTicket.value.messages.push({ is_me: true, text: replyText.value, time: 'Now' });
    replyText.value = '';
};

const markResolved = (id) => {
    allTickets.value = allTickets.value.filter(t => t.id !== id);
    activeTicket.value = null;
    // Because of Vue's reactivity, the badge will disappear automatically
    // if that was the last ticket in that category.
};
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
