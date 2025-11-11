<template>
    <router-link
        :to="to"
        class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors"
        :class="[
      isActive
        ? 'bg-indigo-100 text-indigo-700 font-medium'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
    ]"
    >
        <i :class="`lucide lucide-${icon}`" class="w-5 h-5"></i>
        <span>{{ label }}</span>
    </router-link>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const props = defineProps({
    icon: String,
    label: String,
    to: String,
});

const route = useRoute();
const isActive = computed(() => route.path.startsWith(props.to));
</script>
