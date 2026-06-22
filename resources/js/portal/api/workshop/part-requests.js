import { api } from '../../../plugins/axios';

export const partRequestsApi = {
    index(params) {
        return api.get('/portal/workshop/part-requests', { params });
    },
    show(id) {
        return api.get(`/portal/workshop/part-requests/${id}`);
    },
    store(data) {
        return api.post('/portal/workshop/part-requests', data);
    },
    approve(id, data) {
        return api.post(`/portal/workshop/part-requests/${id}/approve`, data);
    },
    reject(id, data) {
        return api.post(`/portal/workshop/part-requests/${id}/reject`, data);
    },
};
