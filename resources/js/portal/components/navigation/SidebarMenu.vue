<template>
    <aside class="w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col">
        <div class="p-4 text-xl font-bold text-gray-700 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            <span>Martin Logistics</span>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-2">
            <SidebarItem
                v-for="item in visibleMenu"
                :key="item.label"
                :item="item"
            />
        </nav>

        <div class="p-4 border-t border-gray-200 text-sm text-gray-500">
            © {{ new Date().getFullYear() }} Martin Logistics
        </div>
    </aside>
</template>

<script setup>
import { computed } from "vue";
import { useAuthStore } from "../../store/authStore";
import { menu } from "@/config/menu.js";
import SidebarItem from "./SidebarItem.vue";

const authStore = useAuthStore();
const userRoles = computed(() => authStore.user?.roles_list || []);

function isVisible(item) {
    if (!item.roles) return true;
    return item.roles.some(r => userRoles.value.includes(r));
}

function filterMenu(items) {
    return items.reduce((acc, item) => {
        if (!isVisible(item)) return acc;
        if (item.children) {
            const filteredChildren = filterMenu(item.children);
            if (filteredChildren.length === 0) return acc;
            acc.push({ ...item, children: filteredChildren });
        } else {
            acc.push(item);
        }
        return acc;
    }, []);
}

const visibleMenu = computed(() => filterMenu(menu));
</script>
