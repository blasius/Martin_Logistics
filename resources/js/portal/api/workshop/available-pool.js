import { api } from '../../../plugins/axios';

export const availablePoolApi = {
    index(params) {
        return api.get('/portal/workshop/available-pool', { params });
    },
};
