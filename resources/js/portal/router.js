import { createRouter, createWebHistory } from "vue-router";
import PortalLayout from "../portal/layouts/PortalLayout.vue";
import Dashboard from "../portal/views/Dashboard.vue";

export const router = createRouter({
    history: createWebHistory("/portal/"),
    routes: [
        {
            path: "/",
            component: PortalLayout,
            children: [
                { path: "dashboard", component: Dashboard },
                // Add others here: trips, drivers, vehicles...
            ],
        },
    ],
});
