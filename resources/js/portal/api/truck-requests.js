import { api } from '../../plugins/axios';

export const truckRequestsApi = {
    getAll(params = {}) {
        return api.get('/portal/truck-requests', { params });
    },
    show(id) {
        return api.get(`/portal/truck-requests/${id}`);
    },
    create(data) {
        return api.post('/portal/truck-requests', data);
    },
    update(id, data) {
        return api.put(`/portal/truck-requests/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/truck-requests/${id}`);
    },
    queue(params = {}) {
        return api.get('/portal/truck-requests/queue', { params });
    },
    assign(id, data) {
        return api.post(`/portal/truck-requests/${id}/assign`, data);
    },
    availableVehicles(id) {
        return api.get(`/portal/truck-requests/${id}/available-vehicles`);
    },
};

export const dispatchPrepApi = {
    needsPreparation() {
        return api.get('/portal/dispatch-preparation/needs-preparation');
    },
    readyToDepart() {
        return api.get('/portal/dispatch-preparation/ready-to-depart');
    },
    myTrips() {
        return api.get('/portal/dispatch-preparation/my-trips');
    },
    show(tripId) {
        return api.get(`/portal/dispatch-preparation/${tripId}`);
    },
    update(tripId, data) {
        return api.put(`/portal/dispatch-preparation/${tripId}`, data);
    },
    markReady(tripId) {
        return api.post(`/portal/dispatch-preparation/${tripId}/mark-ready`);
    },
};
