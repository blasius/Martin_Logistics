import './bootstrap';
import { createApp } from 'vue';
import clickOutside from './portal/directives/clickOutside';
import { createPinia } from 'pinia';
import router from './portal/router';
import App from './App.vue';
import '../css/app.css';

const app = createApp(App);

app.use(createPinia());
app.use(router);

// Register the directive as 'v-click-outside'
app.directive('click-outside', clickOutside);

app.mount('#app');
