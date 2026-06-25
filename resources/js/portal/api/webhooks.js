import { api } from '../../plugins/axios';

export const webhooksApi = {
    events() {
        return api.get('/portal/webhooks/events');
    },
    subscriptions() {
        return api.get('/portal/webhooks/subscriptions');
    },
    storeSubscription(data) {
        return api.post('/portal/webhooks/subscriptions', data);
    },
    updateSubscription(id, data) {
        return api.put(`/portal/webhooks/subscriptions/${id}`, data);
    },
    deleteSubscription(id) {
        return api.delete(`/portal/webhooks/subscriptions/${id}`);
    },
    deliveries(id) {
        return api.get(`/portal/webhooks/subscriptions/${id}/deliveries`);
    },
    retryDelivery(id) {
        return api.post(`/portal/webhooks/deliveries/${id}/retry`);
    },
};
