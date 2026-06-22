import { api } from '../../../plugins/axios';

export const repairRequestsApi = {
    index(params) {
        return api.get('/portal/workshop/repair-requests', { params });
    },
    store(data) {
        return api.post('/portal/workshop/repair-requests', data);
    },
    show(id) {
        return api.get(`/portal/workshop/repair-requests/${id}`);
    },
    submit(id) {
        return api.post(`/portal/workshop/repair-requests/${id}/submit`);
    },
    requestApproval(id) {
        return api.post(`/portal/workshop/repair-requests/${id}/request-approval`);
    },
    approve(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/approve`, data);
    },
    reject(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/reject`, data);
    },
    assignMechanic(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/assign-mechanic`, data);
    },
    reassignMechanic(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/reassign-mechanic`, data);
    },
    startWork(assignmentId) {
        return api.post(`/portal/workshop/repair-requests/start-work/${assignmentId}`);
    },
    completeWork(assignmentId) {
        return api.post(`/portal/workshop/repair-requests/complete-work/${assignmentId}`);
    },
    useParts(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/use-parts`, data);
    },
    release(id, data) {
        return api.post(`/portal/workshop/repair-requests/${id}/release`, data);
    },
    cancel(id) {
        return api.post(`/portal/workshop/repair-requests/${id}/cancel`);
    },
    mechanics() {
        return api.get('/portal/workshop/repair-requests/mechanics');
    },
    vehicles() {
        return api.get('/portal/workshop/repair-requests/vehicles');
    },
    searchVehicles(q) {
        return api.get('/portal/workshop/repair-requests/search-vehicles', { params: { q } });
    },
};
