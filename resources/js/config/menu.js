// resources/js/config/menu.js

import {
    Home,
    Ticket,
    BarChart3,
    Map,
    User,
    Car,
    Route,
    FileText,
    Settings,
    HelpCircle,
    Radar
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
        label: "Fines",
        icon: Ticket,
        children: [
            { label: "List Fines", to: "/fines" },
            { label: "Analytics", to: "/fines/analytics" },
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
        label: "Settings",
        icon: Settings,
        to: "/settings",
    },
    {
        label: "Support",
        icon: HelpCircle,
        to: "/support",
    },
];
