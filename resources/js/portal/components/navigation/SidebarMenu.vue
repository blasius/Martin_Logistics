<template>
    <aside
        class="w-64 bg-white shadow-lg border-r border-gray-200 flex flex-col"
    >
        <div class="p-4 text-xl font-bold text-gray-700 flex items-center gap-2">
            <i class="lucide lucide-truck text-indigo-600"></i>
            <span>Martin Logistics</span>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-2">
            <SidebarItem icon="home" label="Dashboard" to="/dashboard" />
            <SidebarItem icon="map" label="Trips" to="/trips" />
            <SidebarItem icon="user" label="Drivers" to="/drivers" />
            <SidebarItem icon="car" label="Vehicles" to="/vehicles" />
            <SidebarItem icon="route" label="Routes" to="/routes" />
            <SidebarItem icon="file-text" label="Billing" to="/billing" />
            <SidebarItem icon="bar-chart-3" label="Reports" to="/reports" />
            <SidebarItem icon="settings" label="Settings" to="/settings" />
            <SidebarItem icon="help-circle" label="Support" to="/support" />
        </nav>

        <div class="p-4 border-t border-gray-200 text-sm text-gray-500">
            © 2025 Martin Logistics
        </div>
    </aside>
</template>

<script setup>
import SidebarItem from "./SidebarItem.vue";
</script>
