import { defineStore } from 'pinia';
import api, { ensureCsrfCookie } from '../plugins/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isInitialized: false, // Prevents UI flicker
    }),
    actions: {
        async login(identifier, password) {
            await ensureCsrfCookie();
            const { data } = await api.post('/portal/login', { identifier, password });
            this.user = data.user;
        },

        async logout() {
            await api.post('/logout');
            this.user = null;
            window.location.href = '/portal/login';
        },

        async checkAuth() {
            try {
                const { data } = await api.get('/user');
                this.user = data;
            } catch (e) {
                this.user = null;
            } finally {
                this.isInitialized = true;
            }
        }
    }
});
