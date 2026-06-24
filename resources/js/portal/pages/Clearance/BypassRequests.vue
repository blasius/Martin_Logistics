<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Bypass Requests</h1>
            <div class="flex gap-2">
                <button v-for="s in statuses" :key="s.value"
                    @click="filterStatus = s.value; loadRequests()"
                    :class="['px-3 py-1.5 text-sm font-medium rounded-lg', filterStatus === s.value ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']">
                    {{ s.label }}
                </button>
            </div>
        </div>

        <div class="card">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Trip</th>
                        <th>Check</th>
                        <th>Reason</th>
                        <th>Requested By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests.data" :key="r.id">
                        <td>{{ r.trip?.reference || '-' }}</td>
                        <td>{{ r.check_label }}</td>
                        <td class="max-w-xs truncate">{{ r.reason }}</td>
                        <td>{{ r.requester?.name }}</td>
                        <td>{{ $dayjs(r.created_at).format('YYYY-MM-DD HH:mm') }}</td>
                        <td>
                            <span :class="statusBadge(r.status)">{{ r.status }}</span>
                        </td>
                        <td>
                            <div v-if="r.status === 'pending'" class="flex gap-1">
                                <button @click="openApprove(r)" class="btn btn-xs btn-success">Approve</button>
                                <button @click="openReject(r)" class="btn btn-xs btn-danger">Reject</button>
                            </div>
                            <span v-else-if="r.approver" class="text-xs text-slate-400">by {{ r.approver.name }}</span>
                        </td>
                    </tr>
                    <tr v-if="!requests.data?.length">
                        <td colspan="7" class="text-center text-muted">No requests found</td>
                    </tr>
                </tbody>
            </table>
            <div v-if="requests.last_page > 1" class="flex justify-center gap-2 mt-4">
                <button @click="loadRequests(requests.current_page - 1)" :disabled="!requests.prev_page_url" class="btn btn-sm">Prev</button>
                <span class="self-center text-sm">Page {{ requests.current_page }} of {{ requests.last_page }}</span>
                <button @click="loadRequests(requests.current_page + 1)" :disabled="!requests.next_page_url" class="btn btn-sm">Next</button>
            </div>
        </div>

        <div v-if="showApproveModal" class="modal-backdrop" @click.self="showApproveModal = false">
            <div class="modal-content max-w-md">
                <h2 class="text-xl font-bold mb-4">Approve Bypass</h2>
                <p class="text-sm text-slate-600 mb-4">{{ selectedRequest?.check_label }} — {{ selectedRequest?.trip?.reference }}</p>
                <p class="text-sm mb-4 p-3 bg-slate-50 rounded"><strong>Reason:</strong> {{ selectedRequest?.reason }}</p>
                <form @submit.prevent="approve" class="space-y-4">
                    <div>
                        <label class="label">Comment (optional)</label>
                        <textarea v-model="approveComment" class="textarea w-full" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showApproveModal = false" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-success" :disabled="saving">Approve</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showRejectModal" class="modal-backdrop" @click.self="showRejectModal = false">
            <div class="modal-content max-w-md">
                <h2 class="text-xl font-bold mb-4">Reject Bypass</h2>
                <p class="text-sm text-slate-600 mb-4">{{ selectedRequest?.check_label }} — {{ selectedRequest?.trip?.reference }}</p>
                <p class="text-sm mb-4 p-3 bg-slate-50 rounded"><strong>Reason:</strong> {{ selectedRequest?.reason }}</p>
                <form @submit.prevent="reject" class="space-y-4">
                    <div>
                        <label class="label">Comment (required)</label>
                        <textarea v-model="rejectComment" class="textarea w-full" rows="2" required></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showRejectModal = false" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-danger" :disabled="saving">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { clearanceApi } from "../../api/clearance";

export default {
    data() {
        return {
            requests: {},
            filterStatus: 'pending',
            selectedRequest: null,
            showApproveModal: false,
            showRejectModal: false,
            approveComment: '',
            rejectComment: '',
            saving: false,
            statuses: [
                { value: 'pending', label: 'Pending' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: '', label: 'All' },
            ],
        };
    },
    mounted() {
        this.loadRequests();
    },
    methods: {
        async loadRequests(page = 1) {
            const params = { page, per_page: 20 };
            if (this.filterStatus) params.status = this.filterStatus;
            const { data } = await clearanceApi.bypassRequests(params);
            this.requests = data;
        },
        openApprove(r) {
            this.selectedRequest = r;
            this.approveComment = '';
            this.showApproveModal = true;
        },
        openReject(r) {
            this.selectedRequest = r;
            this.rejectComment = '';
            this.showRejectModal = true;
        },
        async approve() {
            this.saving = true;
            try {
                await clearanceApi.approveBypass(this.selectedRequest.id, { comment: this.approveComment });
                this.showApproveModal = false;
                await this.loadRequests();
            } catch (e) {
                alert(e.response?.data?.message || 'Failed to approve');
            } finally { this.saving = false; }
        },
        async reject() {
            this.saving = true;
            try {
                await clearanceApi.rejectBypass(this.selectedRequest.id, { comment: this.rejectComment });
                this.showRejectModal = false;
                await this.loadRequests();
            } catch (e) {
                alert(e.response?.data?.message || 'Failed to reject');
            } finally { this.saving = false; }
        },
        statusBadge(status) {
            const map = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' };
            return map[status] || 'bg-gray-100 text-gray-600';
        },
    },
};
</script>
