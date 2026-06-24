import { api } from '../../../plugins/axios';

export const serviceQueueApi = {
    index(params) {
        return api.get('/portal/workshop/service-queue', { params });
    },
    store(data) {
        return api.post('/portal/workshop/service-queue', data);
    },
    start(id) {
        return api.post(`/portal/workshop/service-queue/${id}/start`);
    },
    complete(id) {
        return api.post(`/portal/workshop/service-queue/${id}/complete`);
    },
    skip(id) {
        return api.post(`/portal/workshop/service-queue/${id}/skip`);
    },
    reorder(id, data) {
        return api.post(`/portal/workshop/service-queue/${id}/reorder`, data);
    },
    stats() {
        return api.get('/portal/workshop/service-queue/stats');
    },
};
