import { api } from '../../../plugins/axios';

export const fuelTankApi = {
    index() {
        return api.get('/portal/fuel/tanks');
    },
    show(id) {
        return api.get(`/portal/fuel/tanks/${id}`);
    },
    store(data) {
        return api.post('/portal/fuel/tanks', data);
    },
    update(id, data) {
        return api.put(`/portal/fuel/tanks/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/fuel/tanks/${id}`);
    },
    updateLevel(id, data) {
        return api.post(`/portal/fuel/tanks/${id}/update-level`, data);
    },
};
