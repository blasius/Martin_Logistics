import { api } from '../../plugins/axios';

export const schedulingApi = {
    events(start, end) {
        return api.get('/portal/scheduling/events', { params: { start, end } });
    },
    store(data) {
        return api.post('/portal/scheduling/time-slots', data);
    },
    update(id, data) {
        return api.put(`/portal/scheduling/time-slots/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/scheduling/time-slots/${id}`);
    },
};
