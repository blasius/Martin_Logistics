import { api } from '../../plugins/axios';

export const profileApi = {
    getProfile() {
        return api.get('/portal/profile');
    },

    updateProfile(data) {
        return api.put('/portal/profile', data);
    },

    sendResetLink() {
        return api.post('/portal/password/send-reset-link');
    },

    resetPassword(data) {
        return api.post('/portal/password/reset', data);
    }
};
