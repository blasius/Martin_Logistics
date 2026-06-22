import { api } from '../../../plugins/axios';

export const stockMovementsApi = {
    index(params) {
        return api.get('/portal/workshop/stock-movements', { params });
    },
};
