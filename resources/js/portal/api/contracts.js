import { api } from '../../plugins/axios';

export const contractsApi = {
    getAll(params = {}) {
        return api.get('/portal/contracts', { params });
    },
    show(id) {
        return api.get(`/portal/contracts/${id}`);
    },
    create(data) {
        return api.post('/portal/contracts', data);
    },
    update(id, data) {
        return api.put(`/portal/contracts/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/contracts/${id}`);
    },
    expiryWarnings() {
        return api.get('/portal/contracts/expiry-warnings');
    },
    slaStatus() {
        return api.get('/portal/contracts/sla-status');
    },
};
