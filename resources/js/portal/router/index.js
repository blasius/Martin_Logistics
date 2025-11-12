import { createRouter, createWebHistory } from "vue-router";
import DashboardLayout from "../layouts/DashboardLayout.vue";
import Dashboard from "../pages/Dashboard.vue";
import Settings from "../pages/Settings.vue";

export const index = createRouter({
    history: createWebHistory("/portal/"),
    routes: [
        {
            path: "/",
            component: DashboardLayout,
            children: [
                { path: "dashboard", component: Dashboard },
                // Add others here: trips, drivers, vehicles...
            ],
        },
    ],
});
