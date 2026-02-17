// resources/js/portal/store/authStore.js
import { defineStore } from 'pinia';
import { api, ensureCsrfCookie } from '../../plugins/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isInitialized: false,
    }),
    actions: {
        // This is the missing piece the Vue component is looking for
        async login(identifier, password) {
            try {
                await ensureCsrfCookie();
                const { data } = await api.post('portal/login', { identifier, password });

                this.user = data.user;
                this.isInitialized = true; // <--- ADD THIS
                return data;
            } catch (error) {
                this.isInitialized = true; // Even on failure, we are now "initialized"
                throw error;
            }
        },

        // resources/js/portal/store/authStore.js
        async checkAuth() {
            try {
                // This will now hit https://martin-logistics.test/api/user
                const { data } = await api.get('user');
                this.user = data;
            } catch (e) {
                // SILENCE THE ERROR
                this.user = null;
                // We don't re-throw 'e' here, so the app doesn't crash on boot
            } finally {
                this.isInitialized = true;
            }
        },

        async logout() {
            await api.post('logout');
            this.user = null;
            window.location.href = 'portal/login';
        }
    }
});
