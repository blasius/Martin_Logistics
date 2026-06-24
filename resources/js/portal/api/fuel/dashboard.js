import { api } from '../../../plugins/axios';

export const fuelApi = {
    dashboard() {
        return api.get('/portal/fuel/dashboard');
    },
};
