import { api } from '../../../plugins/axios';

export const stockLevelsApi = {
    index(params) {
        return api.get('/portal/workshop/stock-levels', { params });
    },
    store(data) {
        return api.post('/portal/workshop/stock-levels', data);
    },
    update(id, data) {
        return api.put(`/portal/workshop/stock-levels/${id}`, data);
    },
    adjust(data) {
        return api.post('/portal/workshop/stock-levels/adjust', data);
    },
};
