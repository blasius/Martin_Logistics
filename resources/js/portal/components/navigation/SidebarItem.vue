<template>
    <div>
        <!-- Parent item -->
        <div
            @click="toggle"
            class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition hover:bg-gray-100"
            :class="{ 'bg-gray-100 font-semibold': isActiveParent }"
        >
            <div class="flex items-center gap-3">
                <component :is="item.icon" class="w-5 h-5" />
                <span>{{ item.label }}</span>
            </div>

            <!-- Arrow shown only if has children -->
            <svg
                v-if="item.children"
                class="w-4 h-4 transition-transform"
                :class="{ 'rotate-90': open }"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path d="M9 5l7 7-7 7" />
            </svg>
        </div>

        <!-- Children -->
        <div v-if="item.children && open" class="ml-6 mt-1 space-y-1">
            <RouterLink
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50"
                :class="{ 'bg-indigo-50 text-indigo-600 font-semibold': isActive(child.to) }"
            >
                {{ child.label }}
            </RouterLink>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRoute, RouterLink } from "vue-router";

const props = defineProps({
    item: { type: Object, required: true },
});

const route = useRoute();
const open = ref(false);

const toggle = () => {
    if (props.item.children) open.value = !open.value;
    else if (props.item.to) {
        open.value = true;
    }
};

const isActive = (path) => route.path === path;

// Highlight parent if any child is active
const isActiveParent = computed(() => {
    if (!props.item.children) return isActive(props.item.to);
    return props.item.children.some((child) => isActive(child.to));
});
</script>

<style scoped>
.rotate-90 {
    transform: rotate(90deg);
}
</style>
