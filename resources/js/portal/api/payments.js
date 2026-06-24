import { api } from '../../plugins/axios';

export const paymentsApi = {
    index(params) {
        return api.get('/portal/payments', { params });
    },
    show(id) {
        return api.get(`/portal/payments/${id}`);
    },
    store(data) {
        return api.post('/portal/payments', data);
    },
    update(id, data) {
        return api.put(`/portal/payments/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/payments/${id}`);
    },
    aging() {
        return api.get('/portal/payments/aging');
    },
    clientStatement(clientId) {
        return api.get(`/portal/payments/client-statement/${clientId}`);
    },
    downloadClientStatement(clientId) {
        return api.get(`/portal/payments/client-statement/${clientId}/pdf`, {
            responseType: 'blob',
        });
    },
};
