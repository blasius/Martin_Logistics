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
    Settings,
    HelpCircle,
    Radar,
    Zap,
    Crosshair,
    DollarSign,
    Building2,
    ClipboardList,
    Wrench,
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
        to: "/trips",
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
        to: "/billing",
    },
    {
        label: "Reports",
        icon: BarChart3,
        to: "/reports",
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
        ],
    },
    {
        label: "Clients",
        icon: Building2,
        to: "/clients",
    },
    {
        label: "Orders",
        icon: ClipboardList,
        to: "/orders",
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
        label: "Audit Logs",
        icon: ClipboardList,
        to: "/audit-logs",
    },
    {
        label: "Support",
        icon: HelpCircle,
        to: "/support",
    },
];
