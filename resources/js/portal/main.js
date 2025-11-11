import { createApp } from 'vue'
import { createPinia } from 'pinia'
import {router} from './router'
import App from './App.vue'
import '../../css/app.css'

import { api, ensureCsrfCookie, setAuthToken } from "../plugins/axios";

const app = createApp(App)

app.use(createPinia())

// make axios globally accessible
app.config.globalProperties.$axios = api;

// before mount, restore token or CSRF cookie
(async () => {
    const token = localStorage.getItem("token");
    if (token) {
        setAuthToken(token);
    } else {
        await ensureCsrfCookie();
    }

    app.use(router);
    app.mount('#portal-app')
})();
