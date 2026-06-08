import { api } from '../../plugins/axios';

export const currenciesApi = {
    getAll() {
        return api.get('/portal/currencies');
    },

    create(data) {
        return api.post('/portal/currencies', data);
    },

    update(id, data) {
        return api.put(`/portal/currencies/${id}`, data);
    },

    delete(id) {
        return api.delete(`/portal/currencies/${id}`);
    }
};
