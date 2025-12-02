<template>
    <aside class="w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col">
        <div class="p-4 text-xl font-bold text-gray-700 flex items-center gap-2">
            <Car class="w-6 h-6 text-indigo-600" />
            <span>Martin Logistics</span>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-2">
            <SidebarItem
                v-for="item in menu"
                :key="item.label"
                :item="item"
            />
        </nav>

        <div class="p-4 border-t border-gray-200 text-sm text-gray-500">
            © 2025 Martin Logistics
        </div>
    </aside>
</template>

<script setup>
import { menu } from "@/config/menu.js";
import SidebarItem from "./SidebarItem.vue";
</script>
