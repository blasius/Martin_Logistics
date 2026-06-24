import { api } from '../../plugins/axios';

export const expenseTypesApi = {
    getAll(params = {}) {
        return api.get('/portal/expense-types', { params });
    },
    show(id) {
        return api.get(`/portal/expense-types/${id}`);
    },
    create(data) {
        return api.post('/portal/expense-types', data);
    },
    update(id, data) {
        return api.put(`/portal/expense-types/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/expense-types/${id}`);
    },
    submit(id) {
        return api.post(`/portal/expense-types/${id}/submit`);
    },
    approveType(id) {
        return api.post(`/portal/expense-types/${id}/approve`);
    },
    rejectType(id) {
        return api.post(`/portal/expense-types/${id}/reject`);
    },
    categories() {
        return api.get('/portal/expense-types/categories');
    },
};
