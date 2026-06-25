import { api } from '../../plugins/axios';

export const notificationsApi = {
    getAll(params) { return api.get('/portal/notifications', { params }); },
    unreadCount() { return api.get('/portal/notifications/unread-count'); },
    markRead(id) { return api.post(`/portal/notifications/${id}/read`); },
    markAllRead() { return api.post('/portal/notifications/mark-all-read'); },
    destroy(id) { return api.delete(`/portal/notifications/${id}`); },
    getPreferences() { return api.get('/portal/notifications/preferences'); },
    updatePreferences(data) { return api.put('/portal/notifications/preferences', data); },
};
