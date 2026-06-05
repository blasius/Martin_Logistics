<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-3 tracking-tighter uppercase">
                    <LifeBuoy class="text-indigo-600 w-8 h-8" /> Support Command
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="search" placeholder="Search tickets..."
                           class="pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs font-bold bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 w-64" />
                </div>
                <button @click="refresh" :disabled="loading"
                        class="text-[10px] font-black uppercase px-4 py-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95 disabled:opacity-40 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button @click="activeCategoryId = null"
                    :class="[!activeCategoryId ? 'bg-slate-900 text-white shadow-xl translate-y-[-2px]' : 'bg-white text-slate-500 border-slate-200 hover:border-indigo-300']"
                    class="px-6 py-3 rounded-2xl border font-black text-xs uppercase transition-all flex items-center gap-2">
                All Issues <span class="opacity-50 text-[10px]">{{ tickets.length }}</span>
            </button>
            <template v-for="cat in categories" :key="cat.id">
                <button @click="activeCategoryId = cat.id"
                        :class="[activeCategoryId === cat.id ? 'bg-indigo-600 text-white shadow-xl translate-y-[-2px] border-indigo-600' : 'bg-white text-slate-700 border-slate-200 shadow-sm hover:border-indigo-300']"
                        class="px-6 py-3 rounded-2xl border font-black text-xs uppercase transition-all flex items-center gap-3">
                    {{ cat.name }}
                    <span :class="activeCategoryId === cat.id ? 'bg-white text-indigo-600' : 'bg-rose-500 text-white'" class="px-2 py-0.5 rounded-lg text-[10px]">{{ cat.tickets_count }}</span>
                </button>
            </template>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div v-if="loading && !tickets.length" class="p-20 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <table v-else class="w-full text-left">
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
                                {{ userName(t.user) }}
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm tracking-tight">{{ t.user?.name }}</p>
                                <p class="text-[10px] font-bold text-indigo-600 uppercase">{{ subjectLabel(t.subject) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5">
                        <p class="text-sm font-bold text-slate-700 leading-snug">{{ t.title }}</p>
                        <p class="text-[10px] font-black text-slate-400 mt-1 uppercase">{{ t.category?.name }}</p>
                    </td>
                    <td class="p-5">
                        <span :class="statusBadge(t.status)" class="text-[9px] font-black px-2.5 py-1 rounded-lg uppercase tracking-widest">{{ t.status }}</span>
                        <span v-if="t.priority === 'urgent'" class="ml-1.5 text-[9px] font-black px-2 py-1 rounded-lg uppercase tracking-widest bg-rose-100 text-rose-700">URGENT</span>
                    </td>
                    <td class="p-5 text-right">
                        <p class="text-xs font-black text-slate-600">{{ timeAgo(t.created_at) }}</p>
                        <span class="text-[9px] font-bold text-slate-400 uppercase italic">{{ t.messages_count || 0 }} messages</span>
                    </td>
                </tr>
                <tr v-if="!filteredTickets.length">
                    <td colspan="5" class="p-20 text-center text-slate-400 font-black uppercase tracking-widest text-xs">
                        No tickets found
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Transition name="slide">
            <div v-if="activeTicket" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeTicket"></div>
                <div class="relative bg-white w-full max-w-4xl h-full shadow-2xl flex border-l-8 border-indigo-600">
                    <aside class="w-80 border-r border-slate-100 p-8 bg-slate-50/50 overflow-y-auto">
                        <div v-if="loadingDetail" class="flex items-center justify-center py-12">
                            <svg class="w-6 h-6 text-slate-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <template v-if="!loadingDetail && ticketDetail">
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-4">Details</h3>
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-3">
                                        <div>
                                            <p class="text-[9px] font-black text-slate-400 uppercase">Priority</p>
                                            <p class="text-sm font-black mt-0.5" :class="ticketDetail.priority === 'urgent' ? 'text-rose-600' : 'text-slate-800'">{{ ticketDetail.priority }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-slate-400 uppercase">Assigned To</p>
                                            <p class="text-sm font-black mt-0.5 text-slate-800">{{ ticketDetail.assignee?.name || 'Unassigned' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-slate-400 uppercase">Category</p>
                                            <p class="text-sm font-black mt-0.5 text-slate-800">{{ ticketDetail.category?.name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-slate-400 uppercase">Created</p>
                                            <p class="text-sm font-black mt-0.5 text-slate-800">{{ formatDate(ticketDetail.created_at) }}</p>
                                        </div>
                                        <div v-if="ticketDetail.sla_resolution_due_at">
                                            <p class="text-[9px] font-black text-slate-400 uppercase">SLA Due</p>
                                            <p class="text-sm font-black mt-0.5" :class="slaBreached ? 'text-rose-600' : 'text-emerald-600'">{{ formatDate(ticketDetail.sla_resolution_due_at) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-4">Subject</h3>
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                        <div v-if="subjectIsVehicle(ticketDetail.subject)">
                                            <p class="text-lg font-black text-slate-800">{{ ticketDetail.subject.plate_number }}</p>
                                            <p class="text-[10px] font-bold text-slate-400">{{ ticketDetail.subject.make }} {{ ticketDetail.subject.model }}</p>
                                        </div>
                                        <div v-else-if="ticketDetail.subject">
                                            <p class="text-sm font-black text-slate-800">{{ subjectLabel(ticketDetail.subject) }}</p>
                                        </div>
                                        <p v-else class="text-[10px] font-bold text-slate-400">No subject linked</p>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-4">Contact</h3>
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-2">
                                        <p class="text-sm font-black text-slate-800">{{ ticketDetail.user?.name }}</p>
                                        <p class="text-[10px] font-bold text-slate-400">{{ ticketDetail.user?.email }}</p>
                                        <p v-if="ticketDetail.user?.driver?.phone" class="text-[10px] font-bold text-slate-400">{{ ticketDetail.user.driver.phone }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <select v-model="statusUpdate" @change="updateTicketStatus" class="w-full text-[10px] font-black bg-white border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 uppercase tracking-wider">
                                        <option value="" disabled>Change Status</option>
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="waiting">Waiting</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </aside>

                    <div class="flex-1 flex flex-col min-w-0">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                            <div>
                                <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">{{ ticketDetail?.title || 'Loading...' }}</h2>
                                <p v-if="ticketDetail" class="text-[10px] font-black text-slate-400 uppercase">{{ ticketDetail.user?.name }} • {{ subjectLabel(ticketDetail.subject) }}</p>
                            </div>
                            <button @click="closeTicket" class="p-2 bg-slate-50 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-colors">
                                <X class="w-6 h-6" />
                            </button>
                        </div>

                        <div v-if="ticketDetail?.description" class="px-8 py-4 bg-slate-50/50 border-b border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Description</p>
                            <p class="text-sm font-medium text-slate-600">{{ ticketDetail.description }}</p>
                        </div>

                        <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-slate-50/30">
                            <div v-if="loadingDetail" class="flex items-center justify-center py-12">
                                <svg class="w-6 h-6 text-slate-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <template v-if="!loadingDetail">
                                <div v-for="msg in ticketDetail?.messages || []" :key="msg.id"
                                     :class="['flex flex-col max-w-[85%]', msg.user_id === currentUserId ? 'ml-auto items-end' : 'items-start']">
                                    <div :class="[
                                        'p-4 rounded-3xl text-sm font-medium shadow-sm',
                                        msg.user_id === currentUserId ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700'
                                    ]">
                                        {{ msg.message }}
                                    </div>
                                    <span class="text-[9px] font-black text-slate-400 uppercase mt-2 px-2">{{ msg.author?.name }} • {{ timeAgo(msg.created_at) }}</span>
                                </div>
                                <div v-if="!ticketDetail?.messages?.length" class="text-center text-slate-400 font-black uppercase tracking-widest text-xs py-12">
                                    No messages yet
                                </div>
                                <div v-if="ticketDetail?.events?.length" class="border-t border-slate-200 pt-6 mt-8">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-4">Activity Log</p>
                                    <div v-for="evt in ticketDetail.events" :key="evt.id" class="flex items-center gap-3 text-[10px] font-bold text-slate-400 mb-2.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></span>
                                        <span>{{ evt.actor?.name || 'System' }}</span>
                                        <span>{{ evt.type.replace(/_/g, ' ') }}</span>
                                        <span class="ml-auto">{{ timeAgo(evt.created_at) }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="p-6 bg-white border-t border-slate-100">
                            <div class="relative">
                                <textarea v-model="replyText" placeholder="Type message..." @keydown.enter.ctrl="sendReply"
                                          class="w-full bg-slate-50 border-none rounded-3xl p-5 pr-16 text-sm focus:ring-1 focus:ring-indigo-500 min-h-[80px] font-medium resize-none"></textarea>
                                <button @click="sendReply" :disabled="!replyText.trim() || sending"
                                        class="absolute right-4 bottom-4 p-3 bg-indigo-600 text-white rounded-2xl shadow-xl disabled:opacity-40 transition">
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { api } from '../../../plugins/axios'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import { LifeBuoy, X, Send, Search } from 'lucide-vue-next'

dayjs.extend(relativeTime)

const loading = ref(false)
const loadingDetail = ref(false)
const sending = ref(false)
const search = ref('')
const activeCategoryId = ref(null)
const activeTicket = ref(null)
const ticketDetail = ref(null)
const categories = ref([])
const tickets = ref([])
const replyText = ref('')
const statusUpdate = ref('')
const currentUserId = ref(null)

watch(activeCategoryId, () => { fetchTickets() })

const filteredTickets = computed(() => {
    let result = tickets.value
    if (activeCategoryId.value) {
        result = result.filter(t => t.support_category_id === activeCategoryId.value)
    }
    if (search.value) {
        const s = search.value.toLowerCase()
        result = result.filter(t =>
            t.reference?.toLowerCase().includes(s) ||
            t.title?.toLowerCase().includes(s) ||
            t.user?.name?.toLowerCase().includes(s)
        )
    }
    return result
})

const slaBreached = computed(() => {
    if (!ticketDetail.value?.sla_resolution_due_at) return false
    if (['resolved', 'closed'].includes(ticketDetail.value.status)) return false
    return new Date(ticketDetail.value.sla_resolution_due_at) < new Date()
})

const userName = (user) => {
    if (!user?.name) return '??'
    return user.name.substring(0, 2).toUpperCase()
}

const subjectLabel = (subj) => {
    if (!subj) return '—'
    if (subj.plate_number) return subj.plate_number
    if (subj.origin && subj.dest) return `${subj.origin} → ${subj.dest}`
    if (subj.name) return subj.name
    return `#${subj.id}`
}

const subjectIsVehicle = (subj) => {
    return subj?.plate_number || subj?.make
}

const statusBadge = (status) => {
    const map = {
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-amber-100 text-amber-700',
        waiting: 'bg-slate-100 text-slate-600',
        resolved: 'bg-emerald-100 text-emerald-700',
        closed: 'bg-slate-200 text-slate-500',
    }
    return map[status] || 'bg-slate-100 text-slate-600'
}

const timeAgo = (date) => {
    if (!date) return ''
    return dayjs(date).fromNow()
}

const formatDate = (date) => {
    if (!date) return '—'
    return dayjs(date).format('MMM D, YYYY h:mm A')
}

const fetchCategories = async () => {
    try {
        const { data } = await api.get('portal/support/categories')
        categories.value = data
    } catch (e) {
        console.error('Failed to load categories', e)
    }
}

const fetchTickets = async () => {
    loading.value = true
    try {
        const params = {}
        if (activeCategoryId.value) params.category_id = activeCategoryId.value
        const { data } = await api.get('portal/support/tickets', { params })
        tickets.value = data.data || []
        if (!currentUserId.value && data.data?.length) {
            currentUserId.value = data.data[0].user_id
        }
    } catch (e) {
        console.error('Failed to load tickets', e)
    } finally {
        loading.value = false
    }
}

const openTicket = async (ticket) => {
    activeTicket.value = ticket
    loadingDetail.value = true
    ticketDetail.value = null
    statusUpdate.value = ''
    try {
        const { data } = await api.get(`portal/support/tickets/${ticket.id}`)
        ticketDetail.value = data
        if (!currentUserId.value) {
            try {
                const { data: userData } = await api.get('user')
                currentUserId.value = userData.id
            } catch (_) {}
        }
    } catch (e) {
        console.error('Failed to load ticket detail', e)
    } finally {
        loadingDetail.value = false
    }
}

const closeTicket = () => {
    activeTicket.value = null
    ticketDetail.value = null
    statusUpdate.value = ''
    replyText.value = ''
}

const sendReply = async () => {
    if (!replyText.value.trim() || !ticketDetail.value) return
    sending.value = true
    try {
        const { data } = await api.post(`portal/support/tickets/${ticketDetail.value.id}/messages`, {
            message: replyText.value,
        })
        ticketDetail.value.messages.push(data)
        replyText.value = ''
    } catch (e) {
        console.error('Failed to send message', e)
    } finally {
        sending.value = false
    }
}

const updateTicketStatus = async () => {
    if (!statusUpdate.value || !ticketDetail.value) return
    try {
        await api.patch(`portal/support/tickets/${ticketDetail.value.id}/status`, {
            status: statusUpdate.value,
        })
        ticketDetail.value.status = statusUpdate.value
        const idx = tickets.value.findIndex(t => t.id === ticketDetail.value.id)
        if (idx !== -1) tickets.value[idx].status = statusUpdate.value
        fetchCategories()
    } catch (e) {
        console.error('Failed to update status', e)
    }
}

const refresh = () => {
    fetchTickets()
    fetchCategories()
}

let interval
onMounted(async () => {
    await Promise.all([fetchCategories(), fetchTickets()])
    interval = setInterval(refresh, 60000)
})

onUnmounted(() => {
    clearInterval(interval)
})
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
