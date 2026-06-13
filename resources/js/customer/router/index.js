import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import Signup from '../pages/Signup.vue'
import Dashboard from '../pages/Dashboard.vue'
import PlaceOrder from '../pages/PlaceOrder.vue'
import OrderList from '../pages/OrderList.vue'
import TrackOrder from '../pages/TrackOrder.vue'

const routes = [
    { path: '/login', name: 'Login', component: Login, meta: { guestOnly: true } },
    { path: '/signup', name: 'Signup', component: Signup, meta: { guestOnly: true } },
    { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/orders', name: 'Orders', component: OrderList, meta: { requiresAuth: true } },
    { path: '/place-order', name: 'PlaceOrder', component: PlaceOrder, meta: { requiresAuth: true } },
    { path: '/orders/:id', name: 'TrackOrder', component: TrackOrder, meta: { requiresAuth: true }, props: true },
    { path: '/', redirect: '/dashboard' },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
    history: createWebHistory('/customer/'),
    routes,
})

router.beforeEach(async (to, from, next) => {
    const { useAuthStore } = await import('../store/authStore')
    const authStore = useAuthStore()

    if (!authStore.initialized) {
        try { await authStore.checkAuth() } catch {}
    }

    const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
    const guestOnly = to.matched.some(r => r.meta.guestOnly)

    if (requiresAuth && !authStore.user) {
        next({ name: 'Login' })
    } else if (guestOnly && authStore.user) {
        next({ name: 'Dashboard' })
    } else {
        next()
    }
})

export default router
