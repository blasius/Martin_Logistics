import { api } from '../../plugins/axios';

export const expensesApi = {
    getAll(params = {}) {
        return api.get('/portal/expenses', { params });
    },
    show(id) {
        return api.get(`/portal/expenses/${id}`);
    },
    create(data) {
        return api.post('/portal/expenses', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },
    update(id, data) {
        return api.post(`/portal/expenses/${id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },
    destroy(id) {
        return api.delete(`/portal/expenses/${id}`);
    },
    approve(id, comment) {
        return api.post(`/portal/expenses/${id}/approve`, { comment });
    },
    reject(id, reason) {
        return api.post(`/portal/expenses/${id}/reject`, { reason });
    },
    pay(id, data) {
        return api.post(`/portal/expenses/${id}/pay`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },
    dashboard() {
        return api.get('/portal/expenses/dashboard');
    },
    reportByVehicle(params = {}) {
        return api.get('/portal/expenses/reports/by-vehicle', { params });
    },
    reportByCategory(params = {}) {
        return api.get('/portal/expenses/reports/by-category', { params });
    },
    convertFromTicket(ticketId, data) {
        return api.post(`/portal/expenses/convert-from-ticket/${ticketId}`, data);
    },
};
