import { api } from '../../../plugins/axios';

export const workshopDashboardApi = {
    get() {
        return api.get('/portal/workshop/dashboard');
    },
};
