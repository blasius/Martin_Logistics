import api from './index';

export default {
    index(params = {}) {
        return api.get('/returns', { params });
    },
    store(data) {
        return api.post('/returns', data);
    },
    show(id) {
        return api.get(`/returns/${id}`);
    },
    update(id, data) {
        return api.put(`/returns/${id}`, data);
    },
    approve(id) {
        return api.post(`/returns/${id}/approve`);
    },
    reject(id, reason) {
        return api.post(`/returns/${id}/reject`, { reason });
    },
    schedulePickup(id, data) {
        return api.post(`/returns/${id}/schedule-pickup`, data);
    },
    receive(id, data) {
        return api.post(`/returns/${id}/receive`, data);
    },
    complete(id, data) {
        return api.post(`/returns/${id}/complete`, data);
    },
    cancel(id) {
        return api.post(`/returns/${id}/cancel`);
    },
    stats() {
        return api.get('/returns/stats');
    },
};
