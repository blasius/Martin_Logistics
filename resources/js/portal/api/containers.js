import { api } from '../../plugins/axios';

export const containersApi = {
    index(params = {}) {
        return api.get('/portal/containers', { params });
    },
    show(id) {
        return api.get(`/portal/containers/${id}`);
    },
    store(data) {
        return api.post('/portal/containers', data);
    },
    update(id, data) {
        return api.put(`/portal/containers/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/containers/${id}`);
    },
    dashboard() {
        return api.get('/portal/containers/dashboard');
    },
    movements(id) {
        return api.get(`/portal/containers/${id}/movements`);
    },
    recordMovement(data) {
        return api.post('/portal/containers/movements', data);
    },
    calculatePenalties(id) {
        return api.post(`/portal/containers/${id}/calculate-penalties`);
    },
    batchCalculatePenalties() {
        return api.post('/portal/containers/calculate-penalties/batch');
    },
    contracts() {
        return api.get('/portal/containers/contracts');
    },
    storeContract(data) {
        return api.post('/portal/containers/contracts', data);
    },
    searchLocations(q) {
        return api.get('/portal/containers/search-locations', { params: { q } });
    },
};
