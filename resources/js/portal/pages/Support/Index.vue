<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Support Tickets</h1>

        <table class="w-full bg-white rounded shadow">
            <thead>
            <tr class="text-left border-b">
                <th class="p-3">ID</th>
                <th class="p-3">Subject</th>
                <th class="p-3">Category</th>
                <th class="p-3">Priority</th>
                <th class="p-3">Status</th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="t in tickets" :key="t.id" class="border-b">
                <td class="p-3">#{{ t.id }}</td>
                <td class="p-3">{{ t.subject }}</td>
                <td class="p-3">{{ t.category?.name }}</td>
                <td class="p-3 font-semibold">{{ t.priority }}</td>
                <td class="p-3">
            <span class="px-2 py-1 rounded text-sm"
                  :class="badge(t.status)">
              {{ t.status }}
            </span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { api } from "../../../plugins/axios";

const tickets = ref([]);

const badge = (status) => ({
    OPEN: "bg-red-100 text-red-700",
    IN_PROGRESS: "bg-yellow-100 text-yellow-700",
    RESOLVED: "bg-green-100 text-green-700",
    CLOSED: "bg-gray-200 text-gray-700",
}[status]);

onMounted(async () => {
    const res = await api.get("/portal/support/tickets");
    tickets.value = res.data.data;
});
</script>
