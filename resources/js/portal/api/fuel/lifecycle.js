import { api } from '../../../plugins/axios';

export const fuelLifecycleApi = {
    overview(params = {}) {
        return api.get('/portal/fuel/overview', { params });
    },
    reconcile(params = {}) {
        return api.post('/portal/fuel/reconcile', params);
    },
};