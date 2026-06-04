import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../store/authStore";
import DashboardLayout from "../layouts/DashboardLayout.vue";

const router = createRouter({
    history: createWebHistory("/portal/"),
    routes: [
        {
            path: "/login",
            name: "Login",
            component: () => import("../pages/Auth/Login.vue"),
            meta: { guestOnly: true }
        },
        {
            path: "/2fa/setup",
            name: "TwoFactorSetup",
            component: () => import("../pages/Auth/TwoFactorSetup.vue"),
            meta: { guestOnly: true }
        },
        {
            path: "/",
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                { path: "dashboard", component: () => import("../pages/Dashboard.vue") },
                { path: "drivers", component: () => import("../pages/Drivers/Index.vue") },
                { path: "trips", component: () => import("../pages/Trips/Index.vue") },
                { path: "vehicles", component: () => import("../pages/Vehicles/Index.vue") },
                { path: "routes", component: () => import("../pages/Routes/Index.vue") },
                { path: "places", component: () => import("../pages/Places/Index.vue") },
                { path: "tracker", component: () => import("../pages/Tracker/Index.vue") },
                { path: "reports", component: () => import("../pages/Reports/Index.vue") },
                { path: "support", component: () => import("../pages/Support/Index.vue") },
                { path: "settings", component: () => import("../pages/Settings.vue") },
                { path: "fines", component: () => import("../pages/Fines/Index.vue") },
                { path: "fines/analytics", component: () => import("../pages/Fines/Analytics.vue") },
                { path: "control-tower", component: () => import("../pages/ControlTower/Index.vue") },
                { path: "dispatch", component: () => import("../pages/Dispatch/Index.vue") },
                { path: "insurances", component: () => import("../pages/Compliance/Insurance.vue") },
                { path: "inspections", component: () => import("../pages/Compliance/Inspection.vue") },
                { path: "compliance-summary", component: () => import("../pages/Compliance/Index.vue") },
                {
                    path: "drivers/:id",
                    name: "drivers.show",
                    component: () => import("../pages/Drivers/Show.vue"),
                    props: true
                },
                {
                    path: "vehicles/:id",
                    name: "vehicles.show",
                    component: () => import("../pages/Vehicles/Show.vue"),
                    props: true
                },
                {
                    path: "orders/:id",
                    name: "orders.show",
                    component: () => import("../pages/Orders/Show.vue"),
                    props: true
                },
            ],
        },
        { path: "/:pathMatch(.*)*", redirect: "/login" },
    ],
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (!authStore.isInitialized) {
        await authStore.checkAuth();
    }

    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const guestOnly = to.matched.some(record => record.meta.guestOnly);

    if (requiresAuth && !authStore.user) {
        next({ name: 'Login' });
    } else if (guestOnly && authStore.user) {
        next({ name: 'Dashboard' });
    } else {
        next();
    }
});

export default router;