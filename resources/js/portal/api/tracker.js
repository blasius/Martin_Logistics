import { api } from '../../plugins/axios';

export const trackerApi = {
    searchVehicles(q) {
        return api.get('/portal/tracker/search', { params: { q } });
    },

    getVehicle(id, params = {}) {
        return api.get(`/portal/tracker/${id}`, { params });
    }
};
