import { api } from '../../plugins/axios';

export const trackerApi = {
    getAllVehicles(params = {}) {
        return api.get('/portal/tracker', { params });
    },

    searchVehicles(q) {
        return api.get('/portal/tracker/search', { params: { q } });
    },

    getVehicle(id, params = {}) {
        return api.get(`/portal/tracker/${id}`, { params });
    }
};
