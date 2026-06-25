import { customerApi } from './axios';

export const notificationsApi = {
    getAll(params) { return customerApi.get('/customer/notifications', { params }); },
    unreadCount() { return customerApi.get('/customer/notifications/unread-count'); },
    markRead(id) { return customerApi.post(`/customer/notifications/${id}/read`); },
    markAllRead() { return customerApi.post('/customer/notifications/mark-all-read'); },
};
