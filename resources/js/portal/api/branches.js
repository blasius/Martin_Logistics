import { api } from '../../plugins/axios';

export const branchesApi = {
    index(params = {}) {
        return api.get('/portal/branches', { params });
    },
    store(data) {
        return api.post('/portal/branches', data);
    },
    show(id) {
        return api.get(`/portal/branches/${id}`);
    },
    update(id, data) {
        return api.put(`/portal/branches/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/branches/${id}`);
    },
    users(id) {
        return api.get(`/portal/branches/${id}/users`);
    },
    assignUsers(id, user_ids) {
        return api.post(`/portal/branches/${id}/users`, { user_ids });
    },
};
