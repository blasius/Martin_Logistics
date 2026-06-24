<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Aging Report</h1>
            <button @click="refresh" class="btn btn-sm">Refresh</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div v-for="(bucket, key) in report.buckets" :key="key" class="card p-4">
                <h3 class="font-semibold text-sm mb-1">{{ bucket.label }}</h3>
                <p class="text-2xl font-bold">{{ Number(bucket.total).toLocaleString() }}</p>
                <p class="text-xs text-muted">{{ bucket.invoices.length }} invoice(s)</p>
            </div>
        </div>

        <div v-for="(bucket, key) in report.buckets" :key="key" class="card mb-4">
            <h3 class="font-semibold mb-3">{{ bucket.label }}</h3>
            <table v-if="bucket.invoices.length" class="table w-full">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="inv in bucket.invoices" :key="inv.id">
                        <td>
                            <router-link :to="`/invoices/${inv.id}`" class="link">{{ inv.reference }}</router-link>
                        </td>
                        <td>{{ inv.client_name }}</td>
                        <td>{{ Number(inv.total).toLocaleString() }}</td>
                        <td class="font-semibold">{{ Number(inv.balance).toLocaleString() }}</td>
                        <td>{{ inv.due_date }}</td>
                        <td>{{ inv.days_overdue }} days</td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-sm text-muted">No invoices in this bucket</p>
        </div>

        <div class="card p-4">
            <p class="text-lg font-bold">Total Outstanding: {{ Number(report.grand_total).toLocaleString() }}</p>
        </div>
    </div>
</template>

<script>
import { paymentsApi } from "../../api/payments";

export default {
    data() {
        return {
            report: {
                buckets: {
                    current: { label: "0-30 Days", invoices: [], total: 0 },
                    "31_60": { label: "31-60 Days", invoices: [], total: 0 },
                    "61_90": { label: "61-90 Days", invoices: [], total: 0 },
                    "90_plus": { label: "90+ Days", invoices: [], total: 0 },
                },
                grand_total: 0,
            },
        };
    },
    mounted() {
        this.refresh();
    },
    methods: {
        async refresh() {
            const { data } = await paymentsApi.aging();
            this.report = data;
        },
    },
};
</script>
