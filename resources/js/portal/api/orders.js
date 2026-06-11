import { api } from '../../plugins/axios';

export const ordersApi = {
    getAll() {
        return api.get('/portal/orders');
    },

    create(data) {
        return api.post('/portal/orders', data);
    },

    show(id) {
        return api.get(`/portal/orders/${id}`);
    },

    update(id, data) {
        return api.put(`/portal/orders/${id}`, data);
    },

    delete(id) {
        return api.delete(`/portal/orders/${id}`);
    }
};
