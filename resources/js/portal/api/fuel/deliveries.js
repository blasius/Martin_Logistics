import { api } from '../../../plugins/axios';

export const fuelDeliveryApi = {
    index(params) {
        return api.get('/portal/fuel/deliveries', { params });
    },
    show(id) {
        return api.get(`/portal/fuel/deliveries/${id}`);
    },
    store(data) {
        return api.post('/portal/fuel/deliveries', data);
    },
    destroy(id) {
        return api.delete(`/portal/fuel/deliveries/${id}`);
    },
};
