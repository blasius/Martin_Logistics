<template>
    <div class="relative w-full">
        <input
            v-model="query"
            @keyup.enter="search"
            type="text"
            placeholder="Search drivers, trips, orders..."
            class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
        />
        <div
            v-if="results.length"
            class="absolute top-full left-0 right-0 bg-white border rounded-md shadow mt-1 z-10"
        >
            <ul>
                <li
                    v-for="result in results"
                    :key="result.id"
                    class="px-4 py-2 hover:bg-blue-50 cursor-pointer"
                >
                    {{ result.label }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const query = ref('')
const results = ref([])

function search() {
    results.value = query.value
        ? [
            { id: 1, label: 'Driver: John Doe' },
            { id: 2, label: 'Trip: #1245 Kigali → Bujumbura' },
        ]
        : []
}
</script>
