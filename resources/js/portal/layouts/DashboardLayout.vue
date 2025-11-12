<template>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <SidebarMenu />

        <!-- Main content -->
        <div class="flex-1 flex flex-col">
            <!-- Topbar -->
            <Topbar />

            <!-- Main area -->
            <main class="flex-1 overflow-y-auto p-6">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import SidebarMenu from "../components/navigation/SidebarMenu.vue";
import Topbar from "../components/navigation/Topbar.vue";
</script>
