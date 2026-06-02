import { api } from '../../plugins/axios';

export const placesApi = {
    getAllPlaces() {
        return api.get('/portal/places');
    },

    getPlace(id) {
        return api.get(`/portal/places/${id}`);
    },

    createPlace(data) {
        return api.post('/portal/places/store', data);
    },

    updatePlace(id, data) {
        return api.put(`/portal/places/${id}`, data);
    },

    deletePlace(id) {
        return api.delete(`/portal/places/${id}`);
    }
};
