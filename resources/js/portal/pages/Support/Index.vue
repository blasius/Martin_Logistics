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
                <button @click="showCreateTicket = true"
                        class="text-[10px] font-black uppercase px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition active:scale-95 flex items-center gap-1.5 shadow-lg shadow-indigo-200">
                    <Plus class="w-3.5 h-3.5" /> New Ticket
                </button>
                <button @click="refresh" :disabled="loading"
                        class="text-[10px] font-black uppercase px-4 py-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95 disabled:opacity-40 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
                <button @click="openManageCategories"
                        class="text-[10px] font-black uppercase px-3 py-2 rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-300 transition active:scale-95 flex items-center gap-1.5">
                    <Settings class="w-3 h-3" /> Categories
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
                                <p v-if="t.current_vehicle" class="text-[10px] font-bold text-indigo-600 uppercase">{{ t.current_vehicle.plate_number }}</p>
                                <p v-else class="text-[10px] font-bold text-slate-400 uppercase">{{ subjectLabel(t.subject) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5">
                        <p class="text-sm font-bold text-slate-700 leading-snug">{{ t.title }}</p>
                        <span v-if="t.category" :class="categoryColor(t.category.id)" class="mt-1.5 inline-block text-[9px] font-black px-2 py-1 rounded-lg uppercase tracking-widest">{{ t.category.name }}</span>
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

        <!-- Ticket Detail Slideover -->
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
                                    <h3 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-4">Current Vehicle</h3>
                                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                                        <div v-if="ticketDetail.current_vehicle">
                                            <p class="text-lg font-black text-slate-800">{{ ticketDetail.current_vehicle.plate_number }}</p>
                                            <p class="text-[10px] font-bold text-slate-400">{{ ticketDetail.current_vehicle.make }} {{ ticketDetail.current_vehicle.model }}</p>
                                        </div>
                                        <p v-else-if="ticketDetail.subject && subjectIsVehicle(ticketDetail.subject)">
                                            <span class="text-sm font-black text-slate-400">{{ ticketDetail.subject.plate_number }} <span class="text-[10px] font-bold">(previous)</span></span>
                                        </p>
                                        <p v-else class="text-[10px] font-bold text-slate-400">No vehicle assigned</p>
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
                                <p v-if="ticketDetail" class="text-[10px] font-black text-slate-400 uppercase">{{ ticketDetail.user?.name }} &bull; {{ ticketDetail.current_vehicle?.plate_number || subjectLabel(ticketDetail.subject) }}</p>
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
                                    <span class="text-[9px] font-black text-slate-400 uppercase mt-2 px-2">{{ msg.author?.name }} &bull; {{ timeAgo(msg.created_at) }}</span>
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

        <!-- Create Ticket Slideover -->
        <Transition name="slide">
            <div v-if="showCreateTicket" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showCreateTicket = false"></div>
                <div class="relative bg-white w-full max-w-xl h-full shadow-2xl overflow-y-auto border-l-8 border-emerald-600">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter flex items-center gap-3">
                            <Plus class="w-6 h-6 text-emerald-600" /> New Ticket
                        </h2>
                        <button @click="showCreateTicket = false" class="p-2 bg-slate-50 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-colors">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">User</label>
                            <div class="relative">
                                <input v-model="formUserQuery" @input="onUserQueryInput" placeholder="Search by name or email..."
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500" />
                                <div v-if="userSearchResults.length && formUserQuery"
                                     class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto">
                                    <button v-for="u in userSearchResults" :key="u.id" @click="selectUser(u)"
                                            :class="formUserId === u.id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-slate-50'"
                                            class="w-full text-left px-4 py-3 text-sm font-bold border-b border-slate-100 last:border-0 transition-colors">
                                        <span>{{ u.name }}</span>
                                        <span class="text-[10px] font-medium text-slate-400 ml-2">{{ u.email }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="formUserId && formUserName" class="mt-2 text-xs font-bold text-emerald-600">
                                {{ formUserName }}
                            </p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Category</label>
                            <select v-model="formCategoryId" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500">
                                <option value="" disabled>Select category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Vehicle <span class="text-slate-300">(optional)</span></label>
                            <div class="relative">
                                <input v-model="formVehicleQuery" @input="onVehicleQueryInput" placeholder="Search by plate or make..."
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500" />
                                <div v-if="vehicleSearchResults.length && formVehicleQuery"
                                     class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-20 max-h-48 overflow-y-auto">
                                    <button v-for="v in vehicleSearchResults" :key="v.id" @click="selectVehicle(v)"
                                            :class="formVehicleId === v.id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-slate-50'"
                                            class="w-full text-left px-4 py-3 text-sm font-bold border-b border-slate-100 last:border-0 transition-colors">
                                        <span>{{ v.plate_number }}</span>
                                        <span class="text-[10px] font-medium text-slate-400 ml-2">{{ v.make }} {{ v.model }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="formVehicleId && formVehicleLabel" class="mt-2 text-xs font-bold text-slate-500">
                                {{ formVehicleLabel }} <button @click="clearVehicle" class="text-rose-500 hover:text-rose-700 ml-1 text-[10px]">(remove)</button>
                            </p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Priority</label>
                            <select v-model="formPriority" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500">
                                <option value="" disabled>Select priority</option>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Title</label>
                            <input v-model="formTitle" placeholder="Brief title of the issue"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Description</label>
                            <textarea v-model="formDescription" placeholder="Full description of the issue"
                                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 min-h-[120px] resize-none"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button @click="showCreateTicket = false"
                                    class="flex-1 text-[10px] font-black uppercase px-4 py-3 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95">
                                Cancel
                            </button>
                            <button @click="createTicket" :disabled="!formValid || creatingTicket"
                                    class="flex-1 text-[10px] font-black uppercase px-4 py-3 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
                                <svg v-if="creatingTicket" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Create Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Manage Categories Modal -->
        <Transition name="slide">
            <div v-if="showManageCategories" class="fixed inset-0 z-[100] flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeManageCategories"></div>
                <div class="relative bg-white w-full max-w-lg h-full shadow-2xl overflow-y-auto border-l-8 border-amber-500">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter flex items-center gap-3">
                            <Settings class="w-6 h-6 text-amber-500" /> Categories
                        </h2>
                        <button @click="closeManageCategories" class="p-2 bg-slate-50 rounded-xl hover:bg-rose-50 hover:text-rose-600 transition-colors">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                                {{ editingCategory ? 'Edit Category' : 'New Category' }}
                            </h3>
                            <div class="space-y-3">
                                <input v-model="categoryFormName" placeholder="Category name"
                                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500" />
                                <input v-model="categoryFormDescription" placeholder="Description (optional)"
                                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500" />
                                <div class="flex gap-2">
                                    <button v-if="editingCategory" @click="cancelEditCategory"
                                            class="text-[10px] font-black uppercase px-3 py-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95">
                                        Cancel
                                    </button>
                                    <button @click="saveCategory" :disabled="!categoryFormName.trim() || savingCategory"
                                            class="text-[10px] font-black uppercase px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition active:scale-95 disabled:opacity-40">
                                        {{ editingCategory ? 'Update' : 'Add' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div v-for="cat in allCategories" :key="cat.id"
                                 class="flex items-center justify-between bg-white rounded-xl border border-slate-200 px-4 py-3">
                                <div>
                                    <p class="text-sm font-black text-slate-800">{{ cat.name }}</p>
                                    <p v-if="cat.description" class="text-[10px] font-bold text-slate-400">{{ cat.description }}</p>
                                    <p class="text-[9px] font-bold text-slate-300 uppercase mt-0.5">{{ cat.tickets_count }} tickets</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="editCategory(cat)" class="p-2 rounded-xl hover:bg-slate-50 text-slate-400 hover:text-indigo-600 transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button v-if="!cat.tickets_count" @click="confirmDeleteCategory(cat)" class="p-2 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            <div v-if="!allCategories.length" class="text-center text-slate-400 font-black uppercase tracking-widest text-xs py-8">
                                No categories yet
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Delete Category Confirmation -->
        <Transition name="slide">
            <div v-if="deleteConfirmCategoryId" class="fixed inset-0 z-[200] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="deleteConfirmCategoryId = null"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full mx-4 p-8 text-center border-l-8 border-rose-500">
                    <AlertTriangle class="w-12 h-12 text-rose-500 mx-auto mb-4" />
                    <h3 class="text-lg font-black text-slate-900 uppercase mb-2">Delete Category</h3>
                    <p class="text-sm font-medium text-slate-500 mb-6">Are you sure you want to delete "{{ deleteConfirmCategoryName }}"? This cannot be undone.</p>
                    <div class="flex gap-3">
                        <button @click="deleteConfirmCategoryId = null"
                                class="flex-1 text-[10px] font-black uppercase px-4 py-3 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95">
                            Cancel
                        </button>
                        <button @click="deleteCategory" :disabled="deletingCategory"
                                class="flex-1 text-[10px] font-black uppercase px-4 py-3 rounded-xl bg-rose-600 text-white hover:bg-rose-700 transition active:scale-95 disabled:opacity-40">
                            Delete
                        </button>
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
import { LifeBuoy, X, Send, Search, Plus, Settings, Pencil, Trash2, AlertTriangle } from 'lucide-vue-next'

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

// Create Ticket
const showCreateTicket = ref(false)
const formUserId = ref(null)
const formUserName = ref('')
const formUserQuery = ref('')
const formCategoryId = ref('')
const formTitle = ref('')
const formDescription = ref('')
const formPriority = ref('')
const creatingTicket = ref(false)
const userSearchResults = ref([])
let userSearchTimeout = null

// Vehicle search
const formVehicleId = ref(null)
const formVehicleQuery = ref('')
const formVehicleLabel = ref('')
const vehicleSearchResults = ref([])
let vehicleSearchTimeout = null

// Manage Categories
const showManageCategories = ref(false)
const allCategories = ref([])
const editingCategory = ref(null)
const categoryFormName = ref('')
const categoryFormDescription = ref('')
const savingCategory = ref(false)
const deletingCategory = ref(false)
const deleteConfirmCategoryId = ref(null)
const deleteConfirmCategoryName = ref('')

watch(activeCategoryId, () => { fetchTickets() })

const formValid = computed(() =>
    formUserId.value && formCategoryId.value && formTitle.value.trim() && formDescription.value.trim() && formPriority.value
)

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
    if (!subj) return '\u2014'
    if (subj.plate_number) return subj.plate_number
    if (subj.origin && subj.dest) return `${subj.origin} \u2192 ${subj.dest}`
    if (subj.name) return subj.name
    return `#${subj.id}`
}

const subjectIsVehicle = (subj) => {
    return subj?.plate_number || subj?.make
}

const categoryColor = (id) => {
    const palette = [
        'bg-indigo-100 text-indigo-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700',
        'bg-rose-100 text-rose-700',
        'bg-cyan-100 text-cyan-700',
        'bg-violet-100 text-violet-700',
        'bg-pink-100 text-pink-700',
        'bg-orange-100 text-orange-700',
        'bg-teal-100 text-teal-700',
        'bg-lime-100 text-lime-700',
    ]
    return palette[id % palette.length]
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
    if (!date) return '\u2014'
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

const fetchAllCategories = async () => {
    try {
        const { data } = await api.get('portal/support/categories', { params: { all: true } })
        allCategories.value = data
    } catch (e) {
        console.error('Failed to load all categories', e)
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

const searchUsers = async (q) => {
    if (!q) { userSearchResults.value = []; return }
    try {
        const { data } = await api.get('portal/support/users/search', { params: { q } })
        userSearchResults.value = data
    } catch (e) {
        console.error('Failed to search users', e)
    }
}

const onUserQueryInput = () => {
    clearTimeout(userSearchTimeout)
    userSearchTimeout = setTimeout(() => searchUsers(formUserQuery.value), 250)
}

const selectUser = (user) => {
    formUserId.value = user.id
    formUserName.value = user.name
    formUserQuery.value = user.name
    userSearchResults.value = []
}

const searchVehicles = async (q) => {
    if (!q) { vehicleSearchResults.value = []; return }
    try {
        const { data } = await api.get('portal/support/vehicles/search', { params: { q } })
        vehicleSearchResults.value = data
    } catch (e) {
        console.error('Failed to search vehicles', e)
    }
}

const onVehicleQueryInput = () => {
    clearTimeout(vehicleSearchTimeout)
    vehicleSearchTimeout = setTimeout(() => searchVehicles(formVehicleQuery.value), 250)
}

const selectVehicle = (v) => {
    formVehicleId.value = v.id
    formVehicleLabel.value = `${v.plate_number} (${v.make} ${v.model})`
    formVehicleQuery.value = v.plate_number
    vehicleSearchResults.value = []
}

const clearVehicle = () => {
    formVehicleId.value = null
    formVehicleQuery.value = ''
    formVehicleLabel.value = ''
    vehicleSearchResults.value = []
}

const resetForm = () => {
    formUserId.value = null
    formUserName.value = ''
    formUserQuery.value = ''
    formCategoryId.value = ''
    formTitle.value = ''
    formDescription.value = ''
    formPriority.value = ''
    userSearchResults.value = []
    formVehicleId.value = null
    formVehicleQuery.value = ''
    formVehicleLabel.value = ''
    vehicleSearchResults.value = []
}

const createTicket = async () => {
    if (!formValid.value) return
    creatingTicket.value = true
    try {
        const payload = {
            user_id: formUserId.value,
            support_category_id: formCategoryId.value,
            title: formTitle.value,
            description: formDescription.value,
            priority: formPriority.value,
        }
        if (formVehicleId.value) {
            payload.subject_type = 'App\\Models\\Vehicle'
            payload.subject_id = formVehicleId.value
        }
        await api.post('portal/support/tickets', payload)
        showCreateTicket.value = false
        resetForm()
        await fetchTickets()
        await fetchCategories()
    } catch (e) {
        console.error('Failed to create ticket', e)
    } finally {
        creatingTicket.value = false
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

// Category Management
const openManageCategories = () => {
    showManageCategories.value = true
    fetchAllCategories()
    resetCategoryForm()
}

const closeManageCategories = () => {
    showManageCategories.value = false
    resetCategoryForm()
}

const resetCategoryForm = () => {
    editingCategory.value = null
    categoryFormName.value = ''
    categoryFormDescription.value = ''
}

const editCategory = (cat) => {
    editingCategory.value = cat
    categoryFormName.value = cat.name
    categoryFormDescription.value = cat.description || ''
}

const cancelEditCategory = () => {
    resetCategoryForm()
}

const saveCategory = async () => {
    if (!categoryFormName.value.trim()) return
    savingCategory.value = true
    try {
        const payload = { name: categoryFormName.value, description: categoryFormDescription.value }
        if (editingCategory.value) {
            await api.put(`portal/support/categories/${editingCategory.value.id}`, payload)
        } else {
            await api.post('portal/support/categories', payload)
        }
        resetCategoryForm()
        await fetchAllCategories()
        await fetchCategories()
    } catch (e) {
        console.error('Failed to save category', e)
    } finally {
        savingCategory.value = false
    }
}

const confirmDeleteCategory = (cat) => {
    deleteConfirmCategoryId.value = cat.id
    deleteConfirmCategoryName.value = cat.name
}

const deleteCategory = async () => {
    if (!deleteConfirmCategoryId.value) return
    deletingCategory.value = true
    try {
        await api.delete(`portal/support/categories/${deleteConfirmCategoryId.value}`)
        deleteConfirmCategoryId.value = null
        deleteConfirmCategoryName.value = ''
        await fetchAllCategories()
        await fetchCategories()
    } catch (e) {
        console.error('Failed to delete category', e)
    } finally {
        deletingCategory.value = false
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
