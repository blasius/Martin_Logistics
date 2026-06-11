import { api } from '../../plugins/axios';

export const clientsApi = {
    getAll() {
        return api.get('/portal/clients');
    },

    create(data) {
        return api.post('/portal/clients', data);
    },

    update(id, data) {
        return api.put(`/portal/clients/${id}`, data);
    },

    delete(id) {
        return api.delete(`/portal/clients/${id}`);
    }
};
