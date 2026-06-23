import { api } from '../../plugins/axios';

export const settingsApi = {
    getAll() {
        return api.get('/portal/settings');
    },
    update(settings) {
        return api.put('/portal/settings', { settings });
    },
    getFirebaseConfig() {
        return api.get('/portal/settings/firebase');
    },
};
