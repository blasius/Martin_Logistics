import { api } from '../../../plugins/axios';

export const fuelDispenseApi = {
    index(params) {
        return api.get('/portal/fuel/dispenses', { params });
    },
    show(id) {
        return api.get(`/portal/fuel/dispenses/${id}`);
    },
    store(data) {
        return api.post('/portal/fuel/dispenses', data);
    },
    destroy(id) {
        return api.delete(`/portal/fuel/dispenses/${id}`);
    },
    calculate(data) {
        return api.post('/portal/fuel/dispenses/calculate', data);
    },
};
