import { api } from '../../plugins/axios';

export const rateCardsApi = {
    getAll(params = {}) {
        return api.get('/portal/rate-cards', { params });
    },
    show(id) {
        return api.get(`/portal/rate-cards/${id}`);
    },
    create(data) {
        return api.post('/portal/rate-cards', data);
    },
    update(id, data) {
        return api.put(`/portal/rate-cards/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/rate-cards/${id}`);
    },
    calculate(params) {
        return api.post('/portal/rate-cards/calculate', params);
    },
    preview(params) {
        return api.post('/portal/rate-cards/preview', params);
    },
};
