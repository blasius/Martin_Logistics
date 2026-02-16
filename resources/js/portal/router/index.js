import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../store/authStore"; // Adjust path as needed
import DashboardLayout from "../layouts/DashboardLayout.vue";
// ... (Your other imports)

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
            path: "/",
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                { path: "dashboard", component: () => import("../pages/Dashboard.vue") },
                { path: "drivers", component: () => import("../pages/Drivers/Index.vue") },
                { path: "trips", component: () => import("../pages/Trips/Index.vue") },
                { path: "vehicles", component: () => import("../pages/Vehicles/Index.vue") },
                { path: "routes", component: () => import("../pages/Routes/Index.vue") },
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
                // ... (rest of your portal routes)
            ],
        },
        { path: "/:pathMatch(.*)*", redirect: "/login" },
    ],
});

// The Gatekeeper
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // 1. If we haven't checked the session yet (e.g. on Page Refresh), do it now
    if (!authStore.isInitialized) {
        await authStore.checkAuth();
    }

    const isAuthenticated = !!authStore.user;

    // 2. Page requires auth but user is not logged in
    if (to.meta.requiresAuth && !isAuthenticated) {
        return next('/login');
    }

    // 3. User is logged in but tries to access the login page
    if (to.meta.guestOnly && isAuthenticated) {
        return next('/dashboard');
    }

    next();
});

export default router;
