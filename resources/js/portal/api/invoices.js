import { api } from '../../plugins/axios';

export const invoicesApi = {
    getAll(params = {}) {
        return api.get('/portal/invoices', { params });
    },
    show(id) {
        return api.get(`/portal/invoices/${id}`);
    },
    create(data) {
        return api.post('/portal/invoices', data);
    },
    update(id, data) {
        return api.put(`/portal/invoices/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/invoices/${id}`);
    },
    generateFromOrder(orderId) {
        return api.post(`/portal/invoices/generate-from-order/${orderId}`);
    },
    markSent(id) {
        return api.post(`/portal/invoices/${id}/mark-sent`);
    },
    markPaid(id) {
        return api.post(`/portal/invoices/${id}/mark-paid`);
    },
    markOverdue(id) {
        return api.post(`/portal/invoices/${id}/mark-overdue`);
    },
    markCancelled(id) {
        return api.post(`/portal/invoices/${id}/mark-cancelled`);
    },
    downloadPdf(id) {
        return api.get(`/portal/invoices/${id}/pdf`, { responseType: 'blob' });
    },
};
