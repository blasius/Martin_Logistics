<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Fleet overview</h1>
                    <p class="text-slate-500 mt-1">Real-time operations overview</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Last Updated</p>
                        <p class="text-sm font-black text-slate-700">{{ formatTime(lastUpdated) }}</p>
                    </div>
                    <button @click="refreshData" :disabled="loading" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                        <RefreshCw v-if="!loading" class="w-5 h-5" />
                        <div v-else class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>
        </header>

        <!-- Key Metrics Cards -->
        <div class="px-8 py-6">
            <!-- Fleet Utilization Overview -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-8 mb-8 border border-slate-700 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">Fleet Utilization</h2>
                        <p class="text-slate-400 mt-2">Real-time vehicle status and allocation overview</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Total Fleet</p>
                        <p class="text-3xl font-black text-white">{{ fleetUtilizationData.total_vehicles }}</p>
                    </div>
                </div>

                <!-- Utilization Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Active Trucks -->
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-emerald-500 rounded-lg">
                                <Truck class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-full">OPERATIONAL</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fleetUtilizationData.active_vehicles }}</h3>
                        <p class="text-sm text-emerald-300 mt-1">Active Trucks</p>
                        <div class="mt-4">
                            <div class="w-full bg-emerald-500/20 rounded-full h-2">
                                <div class="bg-emerald-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${(fleetUtilizationData.active_vehicles / fleetUtilizationData.total_vehicles) * 100}%` }"></div>
                            </div>
                            <p class="text-xs text-emerald-400 mt-2">{{ Math.round((fleetUtilizationData.active_vehicles / fleetUtilizationData.total_vehicles) * 100) }}% of fleet</p>
                        </div>
                    </div>

                    <!-- Inactive Trucks -->
                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-rose-500 rounded-lg">
                                <XCircle class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-rose-400 bg-rose-500/20 px-2 py-1 rounded-full">OFFLINE</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fleetUtilizationData.inactive_vehicles }}</h3>
                        <p class="text-sm text-rose-300 mt-1">Inactive Trucks</p>
                        <div class="mt-4">
                            <div class="w-full bg-rose-500/20 rounded-full h-2">
                                <div class="bg-rose-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${(fleetUtilizationData.inactive_vehicles / fleetUtilizationData.total_vehicles) * 100}%` }"></div>
                            </div>
                            <p class="text-xs text-rose-400 mt-2">{{ Math.round((fleetUtilizationData.inactive_vehicles / fleetUtilizationData.total_vehicles) * 100) }}% of fleet</p>
                        </div>
                    </div>

                    <!-- Maintenance/Workshop -->
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-500 rounded-lg">
                                <Wrench class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-amber-400 bg-amber-500/20 px-2 py-1 rounded-full">WORKSHOP</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fleetUtilizationData.maintenance_vehicles }}</h3>
                        <p class="text-sm text-amber-300 mt-1">In Maintenance</p>
                        <div class="mt-4">
                            <div class="w-full bg-amber-500/20 rounded-full h-2">
                                <div class="bg-amber-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${(fleetUtilizationData.maintenance_vehicles / fleetUtilizationData.total_vehicles) * 100}%` }"></div>
                            </div>
                            <p class="text-xs text-amber-400 mt-2">{{ Math.round((fleetUtilizationData.maintenance_vehicles / fleetUtilizationData.total_vehicles) * 100) }}% of fleet</p>
                        </div>
                    </div>

                    <!-- Without Drivers -->
                    <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-500 rounded-lg">
                                <UserX class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-purple-400 bg-purple-500/20 px-2 py-1 rounded-full">UNASSIGNED</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fleetUtilizationData.vehicles_without_drivers }}</h3>
                        <p class="text-sm text-purple-300 mt-1">No Driver Assigned</p>
                        <div class="mt-4">
                            <div class="w-full bg-purple-500/20 rounded-full h-2">
                                <div class="bg-purple-400 h-2 rounded-full transition-all duration-500" :style="{ width: `${(fleetUtilizationData.vehicles_without_drivers / fleetUtilizationData.total_vehicles) * 100}%` }"></div>
                            </div>
                            <p class="text-xs text-purple-400 mt-2">{{ Math.round((fleetUtilizationData.vehicles_without_drivers / fleetUtilizationData.total_vehicles) * 100) }}% of fleet</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown -->
                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Utilization Chart -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Utilization Breakdown</h3>
                        <div class="space-y-4">
                            <div v-for="(item, index) in utilizationBreakdown" :key="item.status" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: item.color }"></div>
                                    <span class="text-sm font-medium text-slate-300">{{ item.status }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-black text-white">{{ item.count }}</span>
                                    <span class="text-sm text-slate-400">({{ Math.round((item.count / fleetUtilizationData.total_vehicles) * 100) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Quick Stats</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-3xl font-black text-emerald-400">{{ fleetUtilizationData.utilization_rate }}%</p>
                                <p class="text-sm text-slate-300">Fleet Utilization</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black text-blue-400">{{ fleetUtilizationData.driver_allocation_rate }}%</p>
                                <p class="text-sm text-slate-300">Driver Allocation</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black text-amber-400">{{ fleetUtilizationData.avg_downtime }}hrs</p>
                                <p class="text-sm text-slate-300">Avg Downtime</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black text-purple-400">{{ fleetUtilizationData.available_now }}</p>
                                <p class="text-sm text-slate-300">Available Now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fuel Management Overview -->
            <div class="bg-gradient-to-br from-orange-900 to-red-900 rounded-2xl p-8 mb-8 border border-orange-700 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">Fuel Management</h2>
                        <p class="text-orange-200 mt-2">Real-time fuel monitoring and consumption analytics</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-orange-300 font-medium uppercase tracking-wider">Total Fleet</p>
                        <p class="text-3xl font-black text-white">{{ fuelManagementData.total_fuel_capacity.toLocaleString() }}L</p>
                    </div>
                </div>

                <!-- Fuel Alert Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Critical Fuel Level -->
                    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-500 rounded-lg">
                                <Fuel class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-red-400 bg-red-500/20 px-2 py-1 rounded-full">CRITICAL</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fuelManagementData.vehicles_critical_fuel }}</h3>
                        <p class="text-sm text-red-300 mt-1">Below 10% Fuel Reserve</p>
                        <div class="mt-4 space-y-2">
                            <div v-for="vehicle in fuelManagementData.critical_fuel_vehicles" :key="vehicle.plate" class="flex items-center justify-between text-xs">
                                <span class="text-red-200 font-medium">{{ vehicle.plate }}</span>
                                <span class="text-red-400">{{ vehicle.fuel_percentage }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- High Consumption -->
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-500 rounded-lg">
                                <TrendingUp class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-amber-400 bg-amber-500/20 px-2 py-1 rounded-full">ALERT</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fuelManagementData.vehicles_high_consumption }}</h3>
                        <p class="text-sm text-amber-300 mt-1">Over Normal Rate</p>
                        <div class="mt-4 space-y-2">
                            <div v-for="vehicle in fuelManagementData.high_consumption_vehicles" :key="vehicle.plate" class="flex items-center justify-between text-xs">
                                <span class="text-amber-200 font-medium">{{ vehicle.plate }}</span>
                                <span class="text-amber-400">+{{ vehicle.consumption_rate }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Drainage -->
                    <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-500 rounded-lg">
                                <AlertTriangle class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-purple-400 bg-purple-500/20 px-2 py-1 rounded-full">DRAINAGE</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fuelManagementData.vehicles_fuel_drainage }}</h3>
                        <p class="text-sm text-purple-300 mt-1">Fuel Drained</p>
                        <div class="mt-4 space-y-2">
                            <div v-for="vehicle in fuelManagementData.fuel_drainage_vehicles" :key="vehicle.plate" class="flex items-center justify-between text-xs">
                                <span class="text-purple-200 font-medium">{{ vehicle.plate }}</span>
                                <span class="text-purple-400">{{ vehicle.drained_amount }}L</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Efficiency -->
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-emerald-500 rounded-lg">
                                <Zap class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-full">EFFICIENT</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ fuelManagementData.vehicles_efficient }}</h3>
                        <p class="text-sm text-emerald-300 mt-1">Good Efficiency</p>
                        <div class="mt-4">
                            <p class="text-xs text-emerald-200">Avg: {{ fuelManagementData.avg_efficiency }}L/100km</p>
                            <p class="text-xs text-emerald-400 mt-1">Within normal range</p>
                        </div>
                    </div>
                </div>

                <!-- Fuel Consumption Overview -->
                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Total vs Filled -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Fuel Balance</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-orange-200">Total Consumed Today</span>
                                <span class="text-2xl font-black text-orange-400">{{ fuelManagementData.total_consumed.toLocaleString() }}L</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-emerald-200">Total Filled Today</span>
                                <span class="text-2xl font-black text-emerald-400">{{ fuelManagementData.total_filled.toLocaleString() }}L</span>
                            </div>
                            <div class="border-t border-white/20 pt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">Net Consumption</span>
                                    <span class="text-2xl font-black text-white">{{ fuelManagementData.net_consumption.toLocaleString() }}L</span>
                                </div>
                                <div class="mt-2 w-full bg-white/20 rounded-full h-3">
                                    <div class="bg-gradient-to-r from-emerald-400 to-orange-400 h-3 rounded-full transition-all duration-500" :style="{ width: `${(fuelManagementData.net_consumption / fuelManagementData.total_filled) * 100}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Status Distribution -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Fleet Fuel Status</h3>
                        <div class="space-y-4">
                            <div v-for="(status, index) in fuelStatusDistribution" :key="status.label" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: status.color }"></div>
                                    <span class="text-sm font-medium text-orange-200">{{ status.label }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-black text-white">{{ status.count }}</span>
                                    <span class="text-sm text-orange-300">({{ Math.round((status.count / fuelManagementData.total_vehicles) * 100) }}%)</span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-white/20">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-orange-200">Average Fuel Level</span>
                                    <span class="font-black text-white">{{ fuelManagementData.avg_fuel_level }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- Support System Overview -->
            <div class="bg-gradient-to-br from-indigo-900 to-purple-900 rounded-2xl p-8 mb-8 border border-indigo-700 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">Support System</h2>
                        <p class="text-indigo-200 mt-2">Active support tickets and issue tracking</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-indigo-300 font-medium uppercase tracking-wider">Active Issues</p>
                        <p class="text-3xl font-black text-white">{{ overview.support_overview?.open_tickets || 0 }}</p>
                    </div>
                </div>

                <!-- Support Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Open Tickets -->
                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-500 rounded-lg">
                                <FileText class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-blue-400 bg-blue-500/20 px-2 py-1 rounded-full">OPEN</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ overview.support_overview?.open_tickets || 0 }}</h3>
                        <p class="text-sm text-blue-300 mt-1">Open Tickets</p>
                        <div class="mt-4">
                            <div class="flex items-center gap-2 text-xs text-blue-200">
                                <span>{{ Math.round(((overview.support_overview?.open_tickets || 0) / (overview.support_overview?.total_tickets || 1)) * 100) }}%</span>
                                <span>of total</span>
                            </div>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-500 rounded-lg">
                                <AlertTriangle class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-amber-400 bg-amber-500/20 px-2 py-1 rounded-full">IN PROGRESS</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ overview.support_overview?.in_progress_tickets || 0 }}</h3>
                        <p class="text-sm text-amber-300 mt-1">In Progress</p>
                    </div>

                    <!-- Urgent Tickets -->
                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-rose-500 rounded-lg">
                                <AlertTriangle class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-rose-400 bg-rose-500/20 px-2 py-1 rounded-full">URGENT</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ overview.support_overview?.urgent_tickets || 0 }}</h3>
                        <p class="text-sm text-rose-300 mt-1">Urgent Issues</p>
                    </div>

                    <!-- Resolved Today -->
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-6 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-emerald-500 rounded-lg">
                                <Plus class="w-6 h-6 text-white" />
                            </div>
                            <span class="text-xs font-medium text-emerald-400 bg-emerald-500/20 px-2 py-1 rounded-full">RESOLVED</span>
                        </div>
                        <h3 class="text-3xl font-black text-white">{{ overview.support_overview?.resolved_tickets || 0 }}</h3>
                        <p class="text-sm text-emerald-300 mt-1">Resolved Today</p>
                        <div class="mt-4">
                            <div class="text-xs text-emerald-200">
                                Avg: {{ overview.support_overview?.avg_resolution_hours || 0 }}hrs
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Support Tickets -->
                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Tickets List -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Recent Support Tickets</h3>
                        <div class="space-y-3">
                            <div v-for="ticket in overview.support_overview?.recent_tickets" :key="ticket.id"
                                 class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/10 hover:bg-white/10 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-black text-indigo-600 bg-indigo-100 px-2 py-1 rounded-lg uppercase tracking-widest">
                                            {{ ticket.reference }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-black text-white">{{ ticket.title }}</p>
                                            <p class="text-xs text-indigo-300">{{ ticket.category_name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <span :class="[
                                        'text-[9px] font-black px-2 py-1 rounded-lg uppercase tracking-widest',
                                        ticket.priority === 'urgent' ? 'bg-rose-100 text-rose-700' :
                                        ticket.priority === 'high' ? 'bg-amber-100 text-amber-700' :
                                        'bg-slate-100 text-slate-700'
                                    ]">
                                        {{ ticket.priority }}
                                    </span>
                                    <p class="text-xs text-indigo-300 mt-1">{{ ticket.time_ago }}</p>
                                </div>
                            </div>
                            <div v-if="!overview.support_overview?.recent_tickets?.length" class="text-center py-8 text-indigo-300 text-sm">
                                No recent support tickets
                            </div>
                        </div>
                    </div>

                    <!-- Categories Breakdown -->
                    <div class="bg-white/10 border border-white/20 rounded-xl p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-black text-white uppercase tracking-tight mb-6">Categories</h3>
                        <div class="space-y-3">
                            <div v-for="category in overview.support_overview?.categories" :key="category.name"
                                 class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                                    <span class="text-sm font-medium text-white">{{ category.name }}</span>
                                </div>
                                <span class="text-lg font-black text-indigo-400">{{ category.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Performance & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <!-- Weekly Performance -->
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Weekly Performance</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-3xl font-black text-slate-800">{{ overview.weekly_performance?.weekly_orders || 0 }}</p>
                            <p class="text-xs text-slate-500 mt-1">New Orders</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black text-blue-600">{{ overview.weekly_performance?.weekly_trips || 0 }}</p>
                            <p class="text-xs text-slate-500 mt-1">Trips Created</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black text-emerald-600">{{ overview.weekly_performance?.weekly_delivered || 0 }}</p>
                            <p class="text-xs text-slate-500 mt-1">Delivered</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black text-purple-600">{{ overview.weekly_performance?.delivery_rate || 0 }}%</p>
                            <p class="text-xs text-slate-500 mt-1">Delivery Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <router-link to="/trips" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors group">
                            <div class="p-2 bg-blue-600 rounded-lg group-hover:bg-blue-700 transition-colors">
                                <Plus class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm">Create Trip</p>
                                <p class="text-xs text-slate-500">Dispatch new vehicle</p>
                            </div>
                        </router-link>
                        <router-link to="/dispatch" class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors group">
                            <div class="p-2 bg-emerald-600 rounded-lg group-hover:bg-emerald-700 transition-colors">
                                <Truck class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm">Fleet Control</p>
                                <p class="text-xs text-slate-500">Manage assignments</p>
                            </div>
                        </router-link>
                        <router-link to="/fines" class="flex items-center gap-3 p-3 bg-rose-50 rounded-lg hover:bg-rose-100 transition-colors group">
                            <div class="p-2 bg-rose-600 rounded-lg group-hover:bg-rose-700 transition-colors">
                                <FileText class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <p class="font-black text-slate-800 text-sm">Manage Fines</p>
                                <p class="text-xs text-slate-500">Review violations</p>
                            </div>
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <!-- Recent Orders -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Recent Orders</h3>
                    <div class="space-y-3">
                        <div v-for="order in overview.recent_activity?.recent_orders" :key="order.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div>
                                <p class="font-black text-sm text-slate-800">{{ order.reference }}</p>
                                <p class="text-xs text-slate-500">{{ order.client?.name || 'Unknown Client' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-medium px-2 py-1 rounded-full" :class="getStatusColor(order.status)">
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!overview.recent_activity?.recent_orders?.length" class="text-center py-8 text-slate-400 text-sm">
                            No recent orders
                        </div>
                    </div>
                </div>

                <!-- Recent Trips -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Recent Trips</h3>
                    <div class="space-y-3">
                        <div v-for="trip in overview.recent_activity?.recent_trips" :key="trip.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div>
                                <p class="font-black text-sm text-slate-800">{{ trip.vehicle_plate_snapshot }}</p>
                                <p class="text-xs text-slate-500">{{ trip.driver_name_snapshot }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-medium px-2 py-1 rounded-full" :class="getStatusColor(trip.status)">
                                    {{ trip.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!overview.recent_activity?.recent_trips?.length" class="text-center py-8 text-slate-400 text-sm">
                            No recent trips
                        </div>
                    </div>
                </div>

                <!-- Recent Fines -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Recent Fines</h3>
                    <div class="space-y-3">
                        <div v-for="fine in overview.recent_activity?.recent_fines" :key="fine.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div>
                                <p class="font-black text-sm text-slate-800">{{ fine.plate_number }}</p>
                                <p class="text-xs text-slate-500">{{ fine.ticket_number || 'No #' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-rose-600">RWF{{ formatCurrency(fine.ticket_amount) }}</p>
                                <span class="text-xs font-medium px-2 py-1 rounded-full" :class="getFineStatusColor(fine.status)">
                                    {{ fine.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!overview.recent_activity?.recent_fines?.length" class="text-center py-8 text-slate-400 text-sm">
                            No recent fines
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Truck, MapPin, Users, AlertTriangle, Plus, FileText, RefreshCw, XCircle, Wrench, UserX, Fuel, TrendingUp, Zap, FileText as FileTextIcon } from 'lucide-vue-next';
import { api } from '../../plugins/axios';
import dayjs from 'dayjs';

// State
const loading = ref(false);
const lastUpdated = ref(new Date());
const overview = ref({
    fleet_status: {},
    driver_status: {},
    fuel_management: {},
    support_overview: {},
    order_stats: {},
    trip_stats: {},
    fine_stats: {},
    recent_activity: {},
    weekly_performance: {}
});

// Fleet utilization data will come from API
const fleetUtilizationData = computed(() => {
    const fleet = overview.value.fleet_status || {};
    const drivers = overview.value.driver_status || {};
    const utilization = overview.value.fleet_utilization || {};

    return {
        total_vehicles: fleet.total_vehicles || 0,
        active_vehicles: fleet.active_vehicles || 0,
        inactive_vehicles: fleet.inactive_vehicles || 0,
        maintenance_vehicles: fleet.maintenance_vehicles || 0,
        vehicles_without_drivers: fleet.vehicles_without_drivers || 0,
        utilization_rate: utilization.utilization_rate || 0,
        driver_allocation_rate: utilization.driver_allocation_rate || 0,
        avg_downtime: utilization.avg_downtime || 0,
        available_now: utilization.available_now || 0
    };
});

const utilizationBreakdown = computed(() => {
    const data = fleetUtilizationData.value;
    return [
        { status: 'Active & Operational', count: data.active_vehicles, color: '#10b981' },
        { status: 'Available No Driver', count: data.vehicles_without_drivers, color: '#8b5cf6' },
        { status: 'In Maintenance', count: data.maintenance_vehicles, color: '#f59e0b' },
        { status: 'Inactive', count: data.inactive_vehicles, color: '#ef4444' }
    ];
});

// Mock Fuel Data for Fuel Management View
// Fuel management data will come from API
const fuelManagementData = computed(() => {
    const fuel = overview.value.fuel_management || {};

    return {
        total_fuel_capacity: fuel.total_fuel_capacity || 0,
        total_vehicles: fuel.total_vehicles || 0,
        vehicles_critical_fuel: fuel.vehicles_critical_fuel || 0,
        vehicles_high_consumption: fuel.vehicles_high_consumption || 0,
        vehicles_fuel_drainage: fuel.vehicles_fuel_drainage || 0,
        vehicles_efficient: fuel.vehicles_efficient || 0,
        total_consumed: fuel.total_consumed || 0,
        total_filled: fuel.total_filled || 0,
        net_consumption: fuel.net_consumption || 0,
        avg_fuel_level: fuel.avg_fuel_level || 0,
        avg_efficiency: fuel.avg_efficiency || 0,
        critical_fuel_vehicles: fuel.critical_fuel_vehicles || [],
        high_consumption_vehicles: fuel.high_consumption_vehicles || [],
        fuel_drainage_vehicles: fuel.fuel_drainage_vehicles || []
    };
});

const fuelStatusDistribution = ref([
    { label: 'Critical (below 10%)', count: 4, color: '#ef4444' },
    { label: 'Low (10-25%)', count: 8, color: '#f59e0b' },
    { label: 'Medium (25-50%)', count: 12, color: '#3b82f6' },
    { label: 'Good (50-75%)', count: 16, color: '#10b981' },
    { label: 'Full (above 75%)', count: 8, color: '#059669' }
]);

// Methods
const loadOverview = async () => {
    loading.value = true;
    try {
        const response = await api.get('/portal/dashboard/overview');
        overview.value = response.data;
        lastUpdated.value = new Date();
    } catch (error) {
        console.error('Failed to load dashboard overview:', error);
    } finally {
        loading.value = false;
    }
};

const refreshData = () => {
    loadOverview();
};

const formatTime = (date) => {
    if (!date) return '--:--:--';
    return dayjs(date).format('HH:mm:ss');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
};

const getStatusColor = (status) => {
    const colors = {
        'draft': 'bg-slate-100 text-slate-600',
        'confirmed': 'bg-blue-100 text-blue-600',
        'in_transit': 'bg-amber-100 text-amber-600',
        'delivered': 'bg-emerald-100 text-emerald-600',
        'cancelled': 'bg-rose-100 text-rose-600',
        'pending': 'bg-slate-100 text-slate-600',
        'assigned': 'bg-blue-100 text-blue-600',
        'on_route': 'bg-amber-100 text-amber-600'
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};

const getFineStatusColor = (status) => {
    const colors = {
        'PENDING': 'bg-amber-100 text-amber-600',
        'PAID': 'bg-emerald-100 text-emerald-600',
        'CANCELLED': 'bg-slate-100 text-slate-600',
        'DISPUTED': 'bg-rose-100 text-rose-600'
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};

// Lifecycle
onMounted(() => {
    loadOverview();
    // Auto-refresh every 30 seconds
    setInterval(loadOverview, 30000);
});
</script>

<style scoped>
/* Custom animations and transitions */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bg-white {
    animation: slideIn 0.3s ease-out;
}
</style>
