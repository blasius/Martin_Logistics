<template>
    <div class="relative" ref="dropdownRef">
        <button @click="toggleDropdown" class="relative p-2 rounded-lg hover:bg-gray-50 transition-colors">
            <Bell class="w-5 h-5" :class="unreadCount > 0 ? 'text-orange-500' : 'text-gray-500'" />
            <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <transition enter-active-class="transition-all duration-200 ease-out" leave-active-class="transition-all duration-150 ease-in"
            enter-from-class="opacity-0 scale-95 translate-y-1" leave-to-class="opacity-0 scale-95 translate-y-1">
            <div v-if="dropdownOpen" class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 max-h-[480px] flex flex-col">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-orange-600 hover:text-orange-700 font-medium">Mark all read</button>
                </div>

                <div v-if="loading" class="p-4 space-y-3">
                    <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-gray-100 rounded w-3/4"></div>
                            <div class="h-2 bg-gray-50 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>

                <div v-else-if="notifications.length === 0" class="p-8 text-center">
                    <Bell class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">No notifications yet</p>
                </div>

                <div v-else class="overflow-y-auto flex-1">
                    <div v-for="n in notifications" :key="n.id"
                        @click="handleClick(n)"
                        class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-50 last:border-0"
                        :class="{ 'bg-orange-50/50': !n.read_at }">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                            :class="iconBg(n)">
                            <component :is="iconFor(n)" class="w-4 h-4" :class="iconColor(n)" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700 leading-snug" :class="{ 'font-semibold': !n.read_at }">{{ n.data.reference ? `${n.data.reference}: ` : '' }}{{ messageFor(n) }}</p>
                            <p class="text-[11px] text-gray-400 mt-1">{{ timeAgo(n.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="hasMore" class="border-t border-gray-100 p-2 text-center">
                    <router-link to="/notifications" @click="dropdownOpen = false" class="text-xs text-orange-600 hover:text-orange-700 font-medium">View all notifications</router-link>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Bell, Package, FileText, Truck, CheckCircle } from 'lucide-vue-next';
import { notificationsApi } from '../api/notifications';

const dropdownOpen = ref(false);
const notifications = ref([]);
const loading = ref(false);
const unreadCount = ref(0);
const hasMore = ref(false);
const dropdownRef = ref(null);

async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await notificationsApi.getAll({ per_page: 5 });
        notifications.value = res.data.data;
        hasMore.value = res.data.total > res.data.per_page;
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

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value;
    if (dropdownOpen.value && notifications.value.length === 0) {
        fetchNotifications();
    }
}

function handleClick(n) {
    if (!n.read_at) {
        notificationsApi.markRead(n.id).then(() => {
            n.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        });
    }
    dropdownOpen.value = false;
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
        if (type === 'order_status') return 'bg-orange-100';
        return 'bg-gray-100';
    }
    return 'bg-gray-50';
}

function iconColor(n) {
    const type = n.data?.type;
    if (!n.read_at) {
        if (type === 'invoice_status') return 'text-blue-600';
        if (type === 'delivery_confirmed') return 'text-emerald-600';
        if (type === 'order_status') return 'text-orange-600';
        return 'text-gray-400';
    }
    return 'text-gray-300';
}

function messageFor(n) {
    const d = n.data;
    if (d?.type === 'order_status') {
        return `Order is now ${d.status}`;
    }
    if (d?.type === 'invoice_status') {
        return `Invoice ${d.reference} is ${d.status}`;
    }
    if (d?.type === 'delivery_confirmed') {
        return `Delivered - signed by ${d.received_by}`;
    }
    return 'New notification';
}

function timeAgo(date) {
    const diff = Date.now() - new Date(date).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return new Date(date).toLocaleDateString();
}

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => {
    fetchUnreadCount();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
