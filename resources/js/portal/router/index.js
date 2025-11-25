import { createRouter, createWebHistory } from "vue-router";
import DashboardLayout from "../layouts/DashboardLayout.vue";
import Dashboard from "../pages/Dashboard.vue";
import Drivers from "../pages/Drivers/Index.vue";
import Invoices from "../pages/Invoices/Index.vue";
import Routes from "../pages/Routes/Index.vue";
import Trips from "../pages/Trips/Index.vue";
import Fines from "../pages/Fines/Index.vue";
import Analytics from "../pages/Fines/Analytics.vue";
import Settings from "../pages/Settings.vue";

export const index = createRouter({
    history: createWebHistory("/portal/"),
    routes: [
        {
            path: "/",
            component: DashboardLayout,
            children: [
                { path: "dashboard", component: Dashboard },
                { path: "drivers", component: Drivers },
                { path: "trips", component: Trips },
                { path: "vehicles", component: Dashboard },
                { path: "reports", component: Dashboard },
                { path: "support", component: Dashboard },
                { path: "settings", component: Settings },
                { path: "billing", component: Invoices },
                { path: "routes", component: Routes },
                { path: "fines", component: Fines },
                { path: "fines/analytics", component: Analytics },
                // Add others here: trips, drivers, vehicles...
            ],
        },
    ],
});
