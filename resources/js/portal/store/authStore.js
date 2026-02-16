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
                // 1. Initialize CSRF for the session
                await ensureCsrfCookie();

                // 2. Call the portalLogin method in AuthController
                // Axios uses baseURL: .../api, so this hits /portal/login
                const { data } = await api.post('portal/login', {
                    identifier,
                    password,
                });

                // 3. Save user to state
                this.user = data.user;
                return data;
            } catch (error) {
                // Re-throw so Login.vue can catch it and show the error message
                throw error;
            }
        },

        // resources/js/portal/store/authStore.js
        async checkAuth() {
            try {
                // This will now hit https://martin_logistics.test/api/user
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
            await api.post('/logout');
            this.user = null;
            window.location.href = 'portal/login';
        }
    }
});
