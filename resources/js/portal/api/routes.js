import { api } from '../../plugins/axios';

export const routesApi = {
    getAllRoutes() {
        return api.get('/portal/routes');
    },

    getRoute(id) {
        return api.get(`/portal/routes/${id}`);
    },

    createRoute(data) {
        return api.post('/portal/routes/store', data);
    },

    updateRoute(id, data) {
        return api.put(`/portal/routes/${id}`, data);
    },

    deleteRoute(id) {
        return api.delete(`/portal/routes/${id}`);
    }
};
