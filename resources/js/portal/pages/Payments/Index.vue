<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Payments</h1>
            <button @click="openCreateModal" class="btn btn-primary">
                Record Payment
            </button>
        </div>

        <div class="card mb-6">
            <div class="flex gap-4 flex-wrap">
                <input v-model="filters.invoice_id" type="text" placeholder="Invoice ID" class="input input-sm" />
                <input v-model="filters.client_id" type="text" placeholder="Client ID" class="input input-sm" />
                <select v-model="filters.method" class="select select-sm">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="mobile">Mobile</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
                <input v-model="filters.date_from" type="date" class="input input-sm" />
                <input v-model="filters.date_to" type="date" class="input input-sm" />
                <button @click="loadPayments" class="btn btn-sm">Filter</button>
            </div>
        </div>

        <div class="card">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Paid At</th>
                        <th>Recorded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in payments.data" :key="p.id">
                        <td>{{ p.id }}</td>
                        <td>{{ p.invoice?.reference || '-' }}</td>
                        <td>{{ p.invoice?.client?.name || p.requisition_id ? 'Requisition' : '-' }}</td>
                        <td>{{ Number(p.amount).toLocaleString() }}</td>
                        <td>{{ p.method || '-' }}</td>
                        <td>{{ p.tx_reference || '-' }}</td>
                        <td>{{ p.paid_at ? $dayjs(p.paid_at).format('YYYY-MM-DD') : '-' }}</td>
                        <td>{{ p.cashier?.name || '-' }}</td>
                        <td>
                            <button @click="viewPayment(p)" class="btn btn-xs">View</button>
                        </td>
                    </tr>
                    <tr v-if="!payments.data?.length">
                        <td colspan="9" class="text-center text-muted">No payments found</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="payments.last_page > 1" class="flex justify-center gap-2 mt-4">
                <button @click="loadPayments(payments.current_page - 1)" :disabled="!payments.prev_page_url" class="btn btn-sm">Prev</button>
                <span class="self-center text-sm">Page {{ payments.current_page }} of {{ payments.last_page }}</span>
                <button @click="loadPayments(payments.current_page + 1)" :disabled="!payments.next_page_url" class="btn btn-sm">Next</button>
            </div>
        </div>

        <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
            <div class="modal-content max-w-lg">
                <h2 class="text-xl font-bold mb-4">Record Payment</h2>
                <form @submit.prevent="recordPayment" class="space-y-4">
                    <div>
                        <label class="label">Invoice</label>
                        <select v-model="form.invoice_id" class="select w-full" required>
                            <option value="">Select Invoice</option>
                            <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                                {{ inv.reference }} — {{ inv.client?.name }} — {{ Number(inv.total).toLocaleString() }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Amount</label>
                        <input v-model="form.amount" type="number" step="0.01" min="0.01" class="input w-full" required />
                    </div>
                    <div>
                        <label class="label">Payment Method</label>
                        <select v-model="form.method" class="select w-full">
                            <option value="">Select</option>
                            <option value="cash">Cash</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Transaction Reference</label>
                        <input v-model="form.tx_reference" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Payment Date</label>
                        <input v-model="form.paid_at" type="date" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Notes</label>
                        <textarea v-model="form.notes" class="textarea w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Record Payment' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showViewModal" class="modal-backdrop" @click.self="showViewModal = false">
            <div class="modal-content max-w-md">
                <h2 class="text-xl font-bold mb-4">Payment Details</h2>
                <div class="space-y-2">
                    <p><strong>Invoice:</strong> {{ selectedPayment?.invoice?.reference }}</p>
                    <p><strong>Client:</strong> {{ selectedPayment?.invoice?.client?.name }}</p>
                    <p><strong>Amount:</strong> {{ Number(selectedPayment?.amount).toLocaleString() }}</p>
                    <p><strong>Method:</strong> {{ selectedPayment?.method || '-' }}</p>
                    <p><strong>Reference:</strong> {{ selectedPayment?.tx_reference || '-' }}</p>
                    <p><strong>Paid At:</strong> {{ selectedPayment?.paid_at ? $dayjs(selectedPayment.paid_at).format('YYYY-MM-DD HH:mm') : '-' }}</p>
                    <p><strong>Recorded By:</strong> {{ selectedPayment?.cashier?.name || '-' }}</p>
                    <p v-if="selectedPayment?.notes"><strong>Notes:</strong> {{ selectedPayment.notes }}</p>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="showViewModal = false" class="btn">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { paymentsApi } from "../../api/payments";
import { invoicesApi } from "../../api/invoices";

export default {
    data() {
        return {
            payments: {},
            invoices: [],
            filters: { invoice_id: "", client_id: "", method: "", date_from: "", date_to: "" },
            showCreateModal: false,
            showViewModal: false,
            selectedPayment: null,
            saving: false,
            form: { invoice_id: "", amount: "", method: "", tx_reference: "", paid_at: "", notes: "" },
        };
    },
    mounted() {
        this.loadPayments();
    },
    methods: {
        async loadPayments(page = 1) {
            const params = { page, ...this.filters };
            Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
            const { data } = await paymentsApi.index(params);
            this.payments = data;
        },
        async openCreateModal() {
            this.form = { invoice_id: "", amount: "", method: "", tx_reference: "", paid_at: "", notes: "" };
            const { data } = await invoicesApi.index({ per_page: 200, status: "sent,overdue" });
            this.invoices = data.data || data;
            this.showCreateModal = true;
        },
        async recordPayment() {
            this.saving = true;
            try {
                await paymentsApi.store(this.form);
                this.showCreateModal = false;
                this.loadPayments();
            } catch (e) {
                alert(e.response?.data?.message || "Failed to record payment");
            } finally {
                this.saving = false;
            }
        },
        async viewPayment(p) {
            const { data } = await paymentsApi.show(p.id);
            this.selectedPayment = data;
            this.showViewModal = true;
        },
    },
};
</script>
