import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import '../../css/app.css'

import axios from 'axios'
axios.defaults.withCredentials = true
window.axios = axios

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
