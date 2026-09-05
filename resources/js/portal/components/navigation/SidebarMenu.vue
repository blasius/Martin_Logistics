<template>
    <aside class="w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col">
        <div class="p-4 flex items-center gap-2">
            <img src="/images/martin_hardware_logo.png" alt="Martin Logistics" class="h-10 w-auto">
            <span class="text-xl font-bold text-gray-700">Martin Logistics</span>
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
