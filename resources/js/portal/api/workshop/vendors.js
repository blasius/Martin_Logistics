import { api } from '../../../plugins/axios';

export const vendorsApi = {
    index(params) {
        return api.get('/portal/workshop/vendors', { params });
    },
    store(data) {
        return api.post('/portal/workshop/vendors', data);
    },
    show(id) {
        return api.get(`/portal/workshop/vendors/${id}`);
    },
    update(id, data) {
        return api.put(`/portal/workshop/vendors/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/workshop/vendors/${id}`);
    },
    list() {
        return api.get('/portal/workshop/vendors-list');
    },
};
