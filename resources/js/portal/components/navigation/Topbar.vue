<template>
    <header
        class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6"
    >
        <!-- Global Search -->
        <div class="flex-1 max-w-2xl">
            <GlobalSearch />
        </div>

        <!-- User Actions -->
        <div class="flex items-center gap-4">
            <button class="text-gray-500 hover:text-gray-700">
                <i class="lucide lucide-bell w-5 h-5"></i>
            </button>
            <div class="flex items-center gap-2">
                <img
                    src="/images/avatar-placeholder.png"
                    alt="User Avatar"
                    class="w-8 h-8 rounded-full"
                />
                <span class="text-sm font-medium text-gray-700">Dispatcher</span>
            </div>
        </div>
    </header>
</template>

<script setup>
import GlobalSearch from "../search/GlobalSearch.vue";
</script>
