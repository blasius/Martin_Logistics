import { api } from '../../plugins/axios';

export const exchangeRatesApi = {
    getAll() {
        return api.get('/portal/exchange-rates');
    },

    create(data) {
        return api.post('/portal/exchange-rates', data);
    },

    update(id, data) {
        return api.put(`/portal/exchange-rates/${id}`, data);
    },

    delete(id) {
        return api.delete(`/portal/exchange-rates/${id}`);
    }
};
