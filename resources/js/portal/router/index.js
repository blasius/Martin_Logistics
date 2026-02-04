import { createRouter, createWebHistory } from "vue-router";

// Layout
import DashboardLayout from "../layouts/DashboardLayout.vue";

// Pages
import Dashboard from "../pages/Dashboard.vue";
import Drivers from "../pages/Drivers/Index.vue";
import Trips from "../pages/Trips/Index.vue";
import Vehicles from "../pages/Vehicles/Index.vue";
import Insurances from "../pages/Compliance/Insurance.vue";
import Inspections from "../pages/Compliance/Inspection.vue";
import ComplianceSummary from "../pages/Compliance/Index.vue";
import RoutesPage from "../pages/Routes/Index.vue";
import Invoices from "../pages/Invoices/Index.vue";
import Reports from "../pages/Reports/Index.vue";
import Support from "../pages/Support/Index.vue";
import Settings from "../pages/Settings.vue";

// Fines module
import Fines from "../pages/Fines/Index.vue";
import Analytics from "../pages/Fines/Analytics.vue";

// Control Tower (Live Operational Board)
import ControlTower from "../pages/ControlTower/Index.vue";
import Dispatch from "../pages/Dispatch/Index.vue";

// Changed 'index' to 'router' to match your export and app.js import
const router = createRouter({
    history: createWebHistory("/portal/"),
    routes: [
        {
            path: "/",
            component: DashboardLayout,
            children: [
                { path: "dashboard", component: Dashboard },
                { path: "drivers", component: Drivers },
                { path: "trips", component: Trips },
                { path: "vehicles", component: Vehicles },
                { path: "routes", component: RoutesPage },
                { path: "billing", component: Invoices },
                { path: "reports", component: Reports },
                { path: "support", component: Support },
                { path: "settings", component: Settings },
                { path: "fines", component: Fines },
                { path: "fines/analytics", component: Analytics },
                { path: "control-tower", component: ControlTower },
                { path: "dispatch", component: Dispatch },
                { path: "insurances", component: Insurances },
                { path: "inspections", component: Inspections },
                { path: "compliance-summary", component: ComplianceSummary },
            ],
        },
        // Redirect unknown paths to dashboard
        { path: "/:pathMatch(.*)*", redirect: "/dashboard" },
    ],
});

export default router;
