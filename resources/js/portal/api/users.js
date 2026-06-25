import { api } from '../../plugins/axios';

export const usersApi = {
    getAll(params = {}) {
        return api.get('/portal/users', { params });
    },

    get(id) {
        return api.get(`/portal/users/${id}`);
    },

    create(data) {
        return api.post('/portal/users', data);
    },

    update(id, data) {
        return api.put(`/portal/users/${id}`, data);
    },

    destroy(id) {
        return api.delete(`/portal/users/${id}`);
    },

    rolesList() {
        return api.get('/portal/roles');
    },
};
