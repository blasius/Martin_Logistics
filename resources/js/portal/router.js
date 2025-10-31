import { createRouter, createWebHistory } from 'vue-router'
import Login from './views/Login.vue'
import Dashboard from './views/Dashboard.vue'
import { useAuthStore } from './store'

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: Login },
    { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
]

const router = createRouter({
    history: createWebHistory('/portal/'),
    routes,
})

router.beforeEach((to, from, next) => {
    const store = useAuthStore()
    if (to.meta.requiresAuth && !store.isAuthenticated) next('/login')
    else next()
})

export default router
