import { api } from '../../../plugins/axios';

export const purchaseOrdersApi = {
    index(params) {
        return api.get('/portal/workshop/purchase-orders', { params });
    },
    store(data) {
        return api.post('/portal/workshop/purchase-orders', data);
    },
    show(id) {
        return api.get(`/portal/workshop/purchase-orders/${id}`);
    },
    send(id) {
        return api.post(`/portal/workshop/purchase-orders/${id}/send`);
    },
    confirm(id) {
        return api.post(`/portal/workshop/purchase-orders/${id}/confirm`);
    },
    receive(id, data) {
        return api.post(`/portal/workshop/purchase-orders/${id}/receive`, data);
    },
    cancel(id) {
        return api.post(`/portal/workshop/purchase-orders/${id}/cancel`);
    },
};
