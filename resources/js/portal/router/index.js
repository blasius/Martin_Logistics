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
            path: "/password-reset",
            name: "PasswordReset",
            component: () => import("../pages/Auth/PasswordReset.vue"),
        },
        {
            path: "/",
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                { path: "dashboard", name: "Dashboard", component: () => import("../pages/Dashboard.vue") },
                { path: "drivers", component: () => import("../pages/Drivers/Index.vue") },
                { path: "trips", component: () => import("../pages/Trips/Index.vue") },
                { path: "vehicles", component: () => import("../pages/Vehicles/Index.vue") },
                { path: "routes", component: () => import("../pages/Routes/Index.vue") },
                { path: "places", component: () => import("../pages/Places/Index.vue") },
                { path: "currencies", component: () => import("../pages/Currencies/Index.vue") },
                { path: "exchange-rates", component: () => import("../pages/ExchangeRates/Index.vue") },
                { path: "clients", component: () => import("../pages/Clients/Index.vue") },
                { path: "orders", component: () => import("../pages/Orders/Index.vue") },

                { path: "tracker", component: () => import("../pages/Tracker/Index.vue") },
                { path: "reports", component: () => import("../pages/Reports/Index.vue") },
                { path: "reports/analytics", component: () => import("../pages/Analytics.vue") },
                { path: "support", component: () => import("../pages/Support/Index.vue") },
                { path: "settings", component: () => import("../pages/Settings.vue") },
                { path: "fines", component: () => import("../pages/Fines/Index.vue") },
                { path: "fines/analytics", component: () => import("../pages/Fines/Analytics.vue") },
                { path: "audit-logs", component: () => import("../pages/AuditLogs/Index.vue") },
                { path: "control-tower", component: () => import("../pages/ControlTower/Index.vue") },
                { path: "dispatch", component: () => import("../pages/Dispatch/Index.vue") },
                { path: "intelligence", component: () => import("../pages/RouteIntelligence/Index.vue") },
                { path: "insurances", component: () => import("../pages/Compliance/Insurance.vue") },
                { path: "inspections", component: () => import("../pages/Compliance/Inspection.vue") },
                { path: "compliance-summary", component: () => import("../pages/Compliance/Index.vue") },

                // Workshop & Maintenance
                { path: "workshop/dashboard", component: () => import("../pages/Workshop/Dashboard.vue") },
                { path: "workshop/parts", component: () => import("../pages/Workshop/Parts/Index.vue") },
                { path: "workshop/warehouses", component: () => import("../pages/Workshop/Warehouses/Index.vue") },
                { path: "workshop/stock-levels", component: () => import("../pages/Workshop/StockLevels/Index.vue") },
                { path: "workshop/mechanics", component: () => import("../pages/Workshop/Mechanics/Index.vue") },
                { path: "workshop/stock-movements", component: () => import("../pages/Workshop/StockMovements/Index.vue") },
                { path: "workshop/part-requests", component: () => import("../pages/Workshop/PartRequests/Index.vue") },
                {
                    path: "workshop/part-requests/:id",
                    name: "workshop.part-requests.show",
                    component: () => import("../pages/Workshop/PartRequests/Show.vue"),
                    props: true
                },
                { path: "workshop/vendors", component: () => import("../pages/Workshop/Vendors/Index.vue") },
                { path: "workshop/repair-requests", component: () => import("../pages/Workshop/RepairRequests/Index.vue") },
                {
                    path: "workshop/repair-requests/:id",
                    name: "workshop.repair-requests.show",
                    component: () => import("../pages/Workshop/RepairRequests/Show.vue"),
                    props: true
                },
                { path: "workshop/purchase-orders", component: () => import("../pages/Workshop/PurchaseOrders/Index.vue") },
                { path: "workshop/available-pool", component: () => import("../pages/Workshop/AvailablePool/Index.vue") },
                { path: "workshop/service-queue", component: () => import("../pages/Workshop/ServiceQueue/Index.vue") },
                { path: "workshop/yard", component: () => import("../pages/YardManagement/Index.vue") },
                { path: "workshop/maintenance", component: () => import("../pages/Workshop/Maintenance/Index.vue") },

                // Fuel Management
                { path: "fuel", component: () => import("../pages/Fuel/Dashboard.vue") },
                { path: "fuel/tanks/:id", name: "fuel.tanks.show", component: () => import("../pages/Fuel/Tanks/Show.vue"), props: true },
                { path: "fuel/dispenses", component: () => import("../pages/Fuel/Dispenses/Index.vue") },
                { path: "fuel/reports/pump-to-tank-variance", component: () => import("../pages/Fuel/Reports/PumpToTankVariance.vue") },
                { path: "fuel/analytics/driver-efficiency", component: () => import("../pages/Fuel/Analytics/DriverEfficiency.vue") },
                {
                    path: "workshop/purchase-orders/:id",
                    name: "workshop.purchase-orders.show",
                    component: () => import("../pages/Workshop/PurchaseOrders/Show.vue"),
                    props: true
                },
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
                {
                    path: "proofs-of-delivery",
                    component: () => import("../pages/POD/Index.vue"),
                },
                {
                    path: "proofs-of-delivery/create",
                    component: () => import("../pages/POD/Create.vue"),
                },
                {
                    path: "proofs-of-delivery/:id",
                    name: "pod.show",
                    component: () => import("../pages/POD/Show.vue"),
                    props: true,
                },

                // Rate Cards / Commercial
                { path: "rate-cards", component: () => import("../pages/RateCards/Index.vue") },
                { path: "rate-cards/create", component: () => import("../pages/RateCards/Create.vue") },
                { path: "contracts", component: () => import("../pages/Contracts/Index.vue") },
                { path: "contracts/create", component: () => import("../pages/Contracts/Create.vue") },
                {
                    path: "contracts/:id",
                    name: "contracts.show",
                    component: () => import("../pages/Contracts/Show.vue"),
                    props: true,
                },
                { path: "truck-requests", component: () => import("../pages/TruckRequests/Index.vue") },
                { path: "truck-requests/create", component: () => import("../pages/TruckRequests/Create.vue") },
                {
                    path: "truck-requests/:id",
                    name: "truck-requests.show",
                    component: () => import("../pages/TruckRequests/Show.vue"),
                    props: true,
                },
                { path: "logistics/queue", component: () => import("../pages/Logistics/Queue.vue") },
                { path: "dispatcher", component: () => import("../pages/Dispatcher/Index.vue") },
                { path: "clearance/bypass-requests", component: () => import("../pages/Clearance/BypassRequests.vue") },

                // Invoices
                { path: "invoices", component: () => import("../pages/Invoices/Index.vue") },
                { path: "invoices/create", component: () => import("../pages/Invoices/Create.vue") },
                {
                    path: "invoices/:id",
                    name: "invoices.show",
                    component: () => import("../pages/Invoices/Show.vue"),
                    props: true,
                },

                // Payments / Accounts Receivable
                { path: "payments", component: () => import("../pages/Payments/Index.vue") },
                { path: "payments/aging", component: () => import("../pages/Payments/AgingReport.vue") },

                // Finance / Expense Management
                { path: "finance/expense-types", component: () => import("../pages/Finance/ExpenseTypes/Index.vue") },
                { path: "finance/expense-types/create", component: () => import("../pages/Finance/ExpenseTypes/Create.vue") },
                { path: "finance/expenses", component: () => import("../pages/Finance/Expenses/Index.vue") },
                { path: "finance/expenses/create", component: () => import("../pages/Finance/Expenses/Create.vue") },
                {
                    path: "finance/expenses/:id",
                    name: "finance.expenses.show",
                    component: () => import("../pages/Finance/Expenses/Show.vue"),
                    props: true,
                },

                // Wallet & Ledger
                { path: "wallets", component: () => import("../pages/Wallet/Index.vue") },
                {
                    path: "wallets/:id",
                    name: "wallets.show",
                    component: () => import("../pages/Wallet/Show.vue"),
                    props: true,
                },

                // Containers & Demurrage
                { path: "containers", component: () => import("../pages/Containers/Index.vue") },
                { path: "containers/dashboard", component: () => import("../pages/Containers/Dashboard.vue") },
                { path: "containers/create", component: () => import("../pages/Containers/Create.vue") },
                { path: "containers/contracts/create", component: () => import("../pages/Containers/CreateContract.vue") },
                {
                    path: "containers/:id",
                    name: "containers.show",
                    component: () => import("../pages/Containers/Show.vue"),
                    props: true,
                },

                // Performance Rating & Scoring
                { path: "performance", component: () => import("../pages/Performance/Leaderboard.vue") },
                {
                    path: "performance/drivers/:id",
                    name: "performance.drivers.show",
                    component: () => import("../pages/Performance/DriverProfile.vue"),
                    props: true,
                },
                { path: "performance/rate", component: () => import("../pages/Performance/RateDriver.vue") },
                { path: "performance/rate/driver/:id", component: () => import("../pages/Performance/RateDriver.vue") },
                { path: "performance/rate/dispatcher/:id", component: () => import("../pages/Performance/RateDriver.vue") },
                { path: "performance/dispatchers", component: () => import("../pages/Performance/DispatcherProfiles.vue") },
                { path: "notifications", component: () => import("../pages/Notifications/Index.vue") },
                { path: "documents", component: () => import("../pages/Documents/Templates/Index.vue") },
                { path: "documents/templates", component: () => import("../pages/Documents/Templates/Index.vue") },
                { path: "documents/templates/create", component: () => import("../pages/Documents/Templates/Create.vue") },
                { path: "documents/templates/:id/edit", component: () => import("../pages/Documents/Templates/Create.vue"), props: true },
                { path: "returns", component: () => import("../pages/Returns/Index.vue") },
                { path: "returns/create", component: () => import("../pages/Returns/Create.vue") },
                { path: "returns/:id", component: () => import("../pages/Returns/Show.vue") },
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