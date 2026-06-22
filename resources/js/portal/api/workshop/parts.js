import { api } from '../../../plugins/axios';

export const partsApi = {
    index(params) {
        return api.get('/portal/workshop/parts', { params });
    },
    store(data) {
        return api.post('/portal/workshop/parts', data);
    },
    show(id) {
        return api.get(`/portal/workshop/parts/${id}`);
    },
    update(id, data) {
        return api.put(`/portal/workshop/parts/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/workshop/parts/${id}`);
    },
};
