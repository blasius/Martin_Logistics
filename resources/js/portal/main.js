import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import '../../css/app.css'
import axios from 'axios'

import { api, ensureCsrfCookie, setAuthToken } from "../plugins/axios";
import { useAuthStore } from './store/authStore'; // Import your store

// 1. Force Global Defaults
axios.defaults.withCredentials = true;
window.axios = api;

const app = createApp(App)
const pinia = createPinia() // Create instance to use before mount

app.use(pinia)
app.config.globalProperties.$axios = api;

(async () => {
    const authStore = useAuthStore();

    // 2. Handle Token vs Session
    const token = localStorage.getItem("token");
    if (token) {
        setAuthToken(token);
    } else {
        try {
            // Initialize CSRF first
            await ensureCsrfCookie();
        } catch (e) {
            console.error("CSRF initialization failed. Check your domain/CORS settings.");
        }
    }

    // 3. SILENT AUTH CHECK
    // This stops the 401 error from crashing the app boot.
    // It populates authStore.user if a session exists.
    try {
        await authStore.checkAuth();
    } catch (e) {
        // We ignore the error. If 401, authStore.user stays null.
        console.log("No active session found, proceeding as guest.");
    }

    app.use(router);
    app.mount('#app');
})();
