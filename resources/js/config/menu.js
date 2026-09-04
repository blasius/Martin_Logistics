// resources/js/config/menu.js

import {
    Home,
    Ticket,
    BarChart3,
    Map,
    User,
    Car,
    Route,
    MapPin,
    FileText,
    FolderOpen,
    Settings,
    HelpCircle,
    Radar,
    Zap,
    Crosshair,
    DollarSign,
    Building2,
    ClipboardList,
    Wrench,
    Fuel,
    Receipt,
    Calculator,
    Truck,
    ClipboardCheck,
    Radio,
    Shield,
    BookOpen,
    Calendar,
    GitBranch,
    Package,
    Key,
} from "lucide-vue-next";

export const menu = [
    {
        label: "Dashboard",
        icon: Home,
        to: "/dashboard",
    },
    {
        label: "Control Tower",
        icon: Radar,
        to: "/control-tower",
    },
    {
        label: "Dispatch",
        icon: Zap,
        to: "/dispatch",
    },
    {
        label: "Route Intelligence",
        icon: Crosshair,
        to: "/intelligence",
    },
    {
        label: "Regulatory",
        icon: Ticket,
        children: [
            { label: "Summary", to: "/compliance-summary" },
            { label: "Insurances", to: "/insurances" },
            { label: "Inspections", to: "/inspections" },
            {
                label: "Fines",
                children: [
                    { label: "List Fines", to: "/fines" },
                    { label: "Analytics", to: "/fines/analytics" },
                ],
            },
        ],
    },
    {
        label: "Trips",
        icon: Map,
        children: [
            { label: "All Trips", to: "/trips/all" },
            { label: "Status Board", to: "/trips/board" },
            { label: "New Dispatch", to: "/trips/create" },
        ],
    },
    {
        label: "HR",
        icon: User,
        children: [
            { label: "Dashboard", to: "/hr" },
            { label: "Employees", to: "/hr/employees" },
            { label: "Attendance", to: "/hr/attendance" },
            { label: "Leave", to: "/hr/leave" },
            { label: "Payroll", to: "/hr/payroll" },
        ],
    },
    {
        label: "Drivers",
        icon: User,
        to: "/drivers",
    },
    {
        label: "Vehicles",
        icon: Car,
        to: "/vehicles",
    },
    {
        label: "Routes",
        icon: Route,
        to: "/routes",
    },
    {
        label: "Places",
        icon: MapPin,
        to: "/places",
    },
    {
        label: "Tracker",
        icon: Crosshair,
        to: "/tracker",
    },
    {
        label: "Billing",
        icon: FileText,
        children: [
            { label: "Invoices", to: "/invoices" },
            { label: "Payments", to: "/payments" },
            { label: "Aging Report", to: "/payments/aging" },
        ],
    },
    {
        label: "Dispatch",
        icon: Truck,
        children: [
            { label: "Queue", to: "/logistics/queue" },
            { label: "Preparation", to: "/dispatcher" },
            { label: "Bypass Requests", to: "/clearance/bypass-requests" },
        ],
    },
    {
        label: "Reports",
        icon: BarChart3,
        children: [
            { label: "Reports Dashboard", to: "/reports" },
            { label: "Analytics", to: "/reports/analytics" },
            { label: "Profitability", to: "/reports/profitability" },
            { label: "Performance", to: "/performance" },
        ],
    },
    {
        label: "Workshop",
        icon: Wrench,
        roles: ['Workshop Manager', 'Admin', 'super_admin', 'Operations Manager', 'Logistics Manager'],
        children: [
            { label: "Dashboard", to: "/workshop/dashboard" },
            { label: "Mechanics", to: "/workshop/mechanics" },
            { label: "Repair Requests", to: "/workshop/repair-requests" },
            { label: "Part Requests", to: "/workshop/part-requests" },
            { label: "Parts Catalog", to: "/workshop/parts" },
            { label: "Stock Levels", to: "/workshop/stock-levels" },
            { label: "Stock Movements", to: "/workshop/stock-movements" },
            { label: "Warehouses", to: "/workshop/warehouses" },
            { label: "Vendors", to: "/workshop/vendors" },
            { label: "Purchase Orders", to: "/workshop/purchase-orders" },
            { label: "Available Pool", to: "/workshop/available-pool" },
            { label: "Yard Queue", to: "/workshop/service-queue" },
            { label: "Yard Management", to: "/workshop/yard" },
            { label: "Maintenance", to: "/workshop/maintenance" },
        ],
    },
    {
        label: "Fuel",
        icon: Fuel,
        roles: ['Admin', 'super_admin', 'Operations Manager', 'Logistics Manager'],
        children: [
            { label: "Dashboard", to: "/fuel" },
            { label: "Dispenses", to: "/fuel/dispenses" },
            { label: "Variance Report", to: "/fuel/reports/pump-to-tank-variance" },
            { label: "Driver Efficiency", to: "/fuel/analytics/driver-efficiency" },
        ],
    },
    {
        label: "Clients",
        icon: Building2,
        to: "/clients",
    },
    {
        label: "Finance",
        icon: Receipt,
        children: [
            { label: "Cost Playground", to: "/finance/playground" },
            { label: "Expenses", to: "/finance/expenses" },
            { label: "Expense Types", to: "/finance/expense-types" },
            { label: "Wallets", to: "/wallets" },
        ],
    },
    {
        label: "Containers",
        icon: Truck,
        children: [
            { label: "Dashboard", to: "/containers/dashboard" },
            { label: "Registry", to: "/containers" },
        ],
    },
    {
        label: "Orders",
        icon: ClipboardList,
        children: [
            { label: "All Orders", to: "/orders" },
            { label: "Proof of Delivery", to: "/proofs-of-delivery" },
            { label: "Returns", to: "/returns" },
        ],
    },
    {
        label: "Accounting",
        icon: BookOpen,
        children: [
            { label: "Chart of Accounts", to: "/accounting/chart-of-accounts" },
            { label: "Journal Entries", to: "/accounting/journal-entries" },
            {
                label: "Reports",
                children: [
                    { label: "Trial Balance", to: "/accounting/reports/trial-balance" },
                    { label: "Profit & Loss", to: "/accounting/reports/profit-loss" },
                    { label: "Balance Sheet", to: "/accounting/reports/balance-sheet" },
                ],
            },
        ],
    },
    {
        label: "Roles",
        icon: Shield,
        roles: ['super_admin', 'Admin'],
        to: "/roles",
    },
    {
        label: "Settings",
        icon: Settings,
        to: "/settings",
    },
    {
        label: "Currencies",
        icon: DollarSign,
        children: [
            { label: "Manage Currencies", to: "/currencies" },
            { label: "Exchange Rates", to: "/exchange-rates" },
        ],
    },
    {
        label: "Commercial",
        icon: Calculator,
        children: [
            { label: "Rate Cards", to: "/rate-cards" },
            { label: "Contracts", to: "/contracts" },
            { label: "Truck Requests", to: "/truck-requests" },
            { label: "Logistics Queue", to: "/logistics/queue" },
        ],
    },
    {
        label: "Dispatch Prep",
        icon: ClipboardCheck,
        to: "/dispatcher",
    },
    {
        label: "Documents",
        icon: FolderOpen,
        children: [
            { label: "All Documents", to: "/documents" },
            { label: "Templates", to: "/documents/templates" },
        ],
    },
    {
        label: "Support",
        icon: HelpCircle,
        to: "/support",
    },
    {
        label: "Scheduling",
        icon: Calendar,
        to: "/scheduling",
    },
    {
        label: "Branches",
        icon: GitBranch,
        to: "/branches",
    },
    {
        label: "Load Planner",
        icon: Package,
        to: "/load-planner",
    },
    {
        label: "Developers",
        icon: Key,
        children: [
            { label: "API Keys", to: "/developers/api-keys" },
            { label: "Webhooks", to: "/developers/webhooks" },
        ],
    },
];
