<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            placeholder="Search drivers, trips, vehicles, invoices..."
            class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            @input="onSearch"
        />
        <div
            v-if="results.length && query.length > 1"
            class="absolute z-10 bg-white border rounded-lg shadow mt-1 w-full max-h-60 overflow-y-auto"
        >
            <div
                v-for="(item, i) in results"
                :key="i"
                class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                @click="goTo(item)"
            >
                <span class="font-medium text-gray-800">{{ item.label }}</span>
                <p class="text-xs text-gray-500">{{ item.type }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { api } from "@/plugins/axios.js";

const query = ref("");
const results = ref([]);
const router = useRouter();

let timeout = null;
const onSearch = () => {
    clearTimeout(timeout);
    timeout = setTimeout(async () => {
        if (query.value.length < 2) {
            results.value = [];
            return;
        }
        const { data } = await api.get("/api/search", {
            params: { q: query.value },
        });
        results.value = data.results || [];
    }, 300);
};

const goTo = (item) => {
    router.push(item.url);
    results.value = [];
    query.value = "";
};
</script>
