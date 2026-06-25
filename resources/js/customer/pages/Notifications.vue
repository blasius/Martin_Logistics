<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Notifications</h1>
                <p class="text-sm text-slate-500 mt-1">Stay updated on your orders and invoices</p>
            </div>
            <button v-if="unreadCount > 0" @click="markAllRead"
                class="text-sm text-indigo-600 hover:text-indigo-700 font-medium px-4 py-2 rounded-lg hover:bg-indigo-50 transition-colors">
                Mark all read ({{ unreadCount }})
            </button>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="bg-white rounded-xl border border-slate-100 p-4 animate-pulse">
                <div class="flex gap-3">
                    <div class="w-10 h-10 bg-slate-100 rounded-full flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-50 rounded w-1/3"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="notifications.length === 0" class="bg-white rounded-2xl border border-slate-100 p-12 text-center">
            <Bell class="w-16 h-16 text-slate-200 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-slate-700 mb-1">All caught up!</h3>
            <p class="text-sm text-slate-500">You have no notifications at this time.</p>
        </div>

        <div v-else class="space-y-2">
            <div v-for="n in notifications" :key="n.id"
                @click="handleClick(n)"
                class="bg-white rounded-xl border border-slate-100 p-4 cursor-pointer transition-all hover:shadow-sm"
                :class="{ 'border-l-4 border-l-indigo-500 bg-indigo-50/30': !n.read_at, 'border-l-4 border-l-transparent': !!n.read_at }">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                        :class="iconBg(n)">
                        <component :is="iconFor(n)" class="w-5 h-5" :class="iconColor(n)" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700" :class="{ 'font-semibold': !n.read_at }">{{ messageFor(n) }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ formatTime(n.created_at) }}</p>
                    </div>
                    <span v-if="!n.read_at" class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0 mt-2"></span>
                </div>
            </div>
        </div>

        <div v-if="hasMore" class="mt-6 text-center">
            <button @click="loadMore" :disabled="loadingMore"
                class="text-sm text-indigo-600 hover:text-indigo-700 font-medium px-6 py-2 rounded-lg border border-indigo-200 hover:bg-indigo-50 transition-colors">
                <span v-if="loadingMore">Loading...</span>
                <span v-else>Load more</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Bell, Package, FileText, CheckCircle } from 'lucide-vue-next';
import { notificationsApi } from '../api/notifications';

const notifications = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const unreadCount = ref(0);
const hasMore = ref(false);
let currentPage = 1;

async function fetchNotifications(page = 1) {
    if (page === 1) loading.value = true;
    try {
        const res = await notificationsApi.getAll({ page, per_page: 20 });
        if (page === 1) {
            notifications.value = res.data.data;
        } else {
            notifications.value.push(...res.data.data);
        }
        hasMore.value = res.data.current_page < res.data.last_page;
        currentPage = page;
    } catch {} finally {
        loading.value = false;
    }
}

async function fetchUnreadCount() {
    try {
        const res = await notificationsApi.unreadCount();
        unreadCount.value = res.data.count;
    } catch {}
}

async function loadMore() {
    loadingMore.value = true;
    await fetchNotifications(currentPage + 1);
    loadingMore.value = false;
}

function handleClick(n) {
    if (!n.read_at) {
        notificationsApi.markRead(n.id).then(() => {
            n.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        });
    }
}

async function markAllRead() {
    try {
        await notificationsApi.markAllRead();
        notifications.value.forEach(n => n.read_at = n.read_at || new Date().toISOString());
        unreadCount.value = 0;
    } catch {}
}

function iconFor(n) {
    const type = n.data?.type;
    if (type === 'invoice_status') return FileText;
    if (type === 'delivery_confirmed') return CheckCircle;
    if (type === 'order_status') return Package;
    return Bell;
}

function iconBg(n) {
    const type = n.data?.type;
    if (!n.read_at) {
        if (type === 'invoice_status') return 'bg-blue-100';
        if (type === 'delivery_confirmed') return 'bg-emerald-100';
        if (type === 'order_status') return 'bg-indigo-100';
        return 'bg-slate-100';
    }
    return 'bg-slate-50';
}

function iconColor(n) {
    const type = n.data?.type;
    if (!n.read_at) {
        if (type === 'invoice_status') return 'text-blue-600';
        if (type === 'delivery_confirmed') return 'text-emerald-600';
        if (type === 'order_status') return 'text-indigo-600';
        return 'text-slate-400';
    }
    return 'text-slate-300';
}

function messageFor(n) {
    const d = n.data;
    if (d?.type === 'order_status') return `Order ${d.reference}: status changed to ${d.status}`;
    if (d?.type === 'invoice_status') return `Invoice ${d.reference}: ${d.status}`;
    if (d?.type === 'delivery_confirmed') return `Order ${d.reference} delivered - signed by ${d.received_by}`;
    return 'New notification';
}

function formatTime(date) {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

onMounted(() => {
    fetchNotifications();
    fetchUnreadCount();
});
</script>
