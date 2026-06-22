import { api } from '../../../plugins/axios';

export const warehousesApi = {
    index(params) {
        return api.get('/portal/workshop/warehouses', { params });
    },
    store(data) {
        return api.post('/portal/workshop/warehouses', data);
    },
    show(id) {
        return api.get(`/portal/workshop/warehouses/${id}`);
    },
    update(id, data) {
        return api.put(`/portal/workshop/warehouses/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/workshop/warehouses/${id}`);
    },
    list() {
        return api.get('/portal/workshop/warehouses-list');
    },
};
