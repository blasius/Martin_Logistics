<template>
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Fleet Reports</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Real-time fleet intelligence</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <select v-model="selectedCurrencyId" @change="onCurrencyChange"
                        class="text-[10px] font-black uppercase px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 outline-none focus:ring-2 focus:ring-indigo-200 transition cursor-pointer">
                    <option v-for="c in currencies" :key="c.id" :value="c.id">
                        {{ c.code }} — {{ c.name }}
                    </option>
                </select>
                <button @click="refresh" :disabled="loading"
                        class="text-[10px] font-black uppercase px-4 py-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition active:scale-95 disabled:opacity-40 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Fleet Overview -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fleet Overview</h2>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <StatCard label="Total Vehicles" :value="data?.fleet?.total_vehicles" color="slate" />
                <StatCard label="Active" :value="data?.fleet?.active" color="emerald" />
                <StatCard label="Moving Now" :value="data?.fleet?.moving_now" color="blue" />
                <StatCard label="Maintenance" :value="data?.fleet?.maintenance" color="amber" />
                <StatCard label="Inactive" :value="data?.fleet?.inactive" color="red" />
                <StatCard label="Utilization" :value="data?.fleet?.utilization_rate + '%'" color="purple" />
            </div>
            <div class="px-6 pb-6 flex gap-4 text-[10px] font-bold text-slate-400">
                <span>Trailers: <strong class="text-slate-700">{{ data?.fleet?.active_trailers }}/{{ data?.fleet?.total_trailers }}</strong> active</span>
                <span>No driver: <strong class="text-red-500">{{ data?.fleet?.vehicles_without_drivers }}</strong></span>
                <span>Idle: <strong class="text-amber-500">{{ data?.fleet?.idle }}</strong></span>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Business Metrics / Top Clients -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Top Profitable Clients</h2>
                </div>
                <div class="p-4" style="height: 280px">
                    <canvas ref="clientChart"></canvas>
                </div>
                <div class="px-6 pb-4 flex gap-4 text-[10px] font-bold text-slate-400">
                    <span>Total: <strong class="text-slate-700">{{ formatCurrency(data?.business?.total_revenue) }}</strong></span>
                    <span>Month: <strong class="text-emerald-600">{{ formatCurrency(data?.business?.month_revenue) }}</strong></span>
                    <span>Delivered: <strong class="text-slate-700">{{ data?.business?.delivered_orders }}</strong></span>
                </div>
            </section>

            <!-- Compliance -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Compliance</h2>
                    <span class="text-[9px] font-black px-2 py-0.5 rounded-md"
                          :class="healthColor">
                        {{ data?.compliance?.health_percentage }}% Healthy
                    </span>
                </div>
                <div class="p-6 space-y-4">
                    <MetricRow label="Fleet Health" :value="data?.compliance?.health_percentage + '%'" />
                    <MetricRow label="Grounded" :value="data?.compliance?.grounded" warning />
                    <MetricRow label="Insurance Expiring (30d)" :value="data?.compliance?.insurance_expiring_30d" />
                    <MetricRow label="Inspections Overdue" :value="data?.compliance?.inspections_overdue" warning />
                    <MetricRow label="Fines Pending" :value="data?.compliance?.fines_pending" />
                    <MetricRow label="Licences Expiring (30d)" :value="data?.compliance?.licences_expiring_30d" />
                    <div v-if="data?.compliance?.grounded_list?.length" class="border-t border-slate-100 pt-3 mt-3">
                        <p class="text-[9px] font-black text-red-400 uppercase tracking-wider mb-2">Grounded Units</p>
                        <div v-for="u in data.compliance.grounded_list" :key="u.plate"
                             class="flex items-center gap-2 text-[10px] font-bold text-slate-600 mb-1">
                            <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                            <span>{{ u.plate }}</span>
                            <span class="text-slate-400">({{ u.type }})</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Fuel -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fuel Overview</h2>
                </div>
                <div class="p-6 space-y-4">
                    <MetricRow label="Avg Fuel Level" :value="data?.fuel?.avg_fuel_level + '%'" />
                    <MetricRow label="Critical Fuel" :value="data?.fuel?.critical_count" warning />
                    <MetricRow label="Today Refilled" :value="data?.fuel?.today_filled + 'L'" />
                    <MetricRow label="Today Theft/Drain" :value="data?.fuel?.today_stolen + 'L'" warning />
                    <MetricRow label="Month Refilled" :value="data?.fuel?.month_filled + 'L'" />
                    <MetricRow label="Month Theft/Drain" :value="data?.fuel?.month_stolen + 'L'" warning />
                </div>
            </section>
        </div>

        <!-- Financial: Cost vs Revenue -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Cost vs Revenue</h2>
                <span v-if="data?.financial?.profit_margin !== undefined"
                      class="text-[9px] font-black px-2 py-0.5 rounded-md"
                      :class="data.financial.profit_margin >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                    {{ data.financial.profit_margin >= 0 ? '+' : '' }}{{ data.financial.profit_margin }}% Margin
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Total Revenue" :value="formatCurrency(data?.financial?.total_revenue)" color="emerald" />
                    <StatCard label="Total Expenses" :value="formatCurrency(data?.financial?.total_expenses)" color="red" />
                    <StatCard label="Net Profit" :value="formatCurrency(data?.financial?.net_profit)" :color="profitColor" />
                    <StatCard label="Profit Margin" :value="data?.financial?.profit_margin + '%'" :color="profitColor" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monthly Revenue vs Expenses Chart -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Revenue vs Expenses (12 months)</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200" style="height: 220px">
                            <canvas ref="financialChart"></canvas>
                        </div>
                    </div>

                    <!-- Cost Breakdown -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Cost Breakdown by Type</p>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2">
                            <div v-for="item in costBreakdown" :key="item.name"
                                 class="flex items-center gap-3">
                                <span class="text-[10px] font-bold text-slate-600 w-24 shrink-0 truncate">{{ item.name }}</span>
                                <div class="flex-1 bg-slate-200 rounded-full h-3 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :style="{ width: costBarWidth(item.amount), background: costColor(item.name) }"></div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 w-20 text-right">{{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div v-if="!costBreakdown.length" class="text-[10px] text-slate-400 text-center py-4">No expense data</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Trip Timeline -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Trips This Week</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="tripChart"></canvas>
                </div>
            </section>

            <!-- Order Status Distribution -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Order Status</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="orderChart"></canvas>
                </div>
            </section>

            <!-- Orders This Week -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Orders This Week</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="orderTimelineChart"></canvas>
                </div>
            </section>

            <!-- Fuel Consumption -->
            <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fuel Refilled (12 months)</h2>
                </div>
                <div class="p-4" style="height: 200px">
                    <canvas ref="fuelChart"></canvas>
                </div>
            </section>
        </div>

        <!-- ===== NEW: Container Overview ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Container Overview</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Total Containers" :value="ud?.containers?.total" color="slate" />
                    <StatCard label="Active" :value="ud?.containers?.active" color="emerald" />
                    <StatCard label="Owned" :value="ud?.containers?.owned" color="blue" />
                    <StatCard label="Leased" :value="ud?.containers?.leased" color="amber" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Status Distribution</p>
                        <div class="space-y-2">
                            <div v-for="(count, status) in containerStatusDist" :key="status"
                                 class="flex items-center gap-3 text-[10px] font-bold">
                                <span class="w-3 h-3 rounded-full shrink-0" :class="containerStatusColor(status)"></span>
                                <span class="text-slate-600 capitalize w-28">{{ status.replace(/_/g, ' ') }}</span>
                                <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-400 transition-all" :style="{ width: containerBarWidth(count) }"></div>
                                </div>
                                <span class="text-slate-500 w-10 text-right">{{ count }}</span>
                            </div>
                            <div v-if="!Object.keys(containerStatusDist).length" class="text-[10px] text-slate-400 py-2">No container data</div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <MetricRow label="Total Penalties" :value="formatCurrency(ud?.containers?.total_penalties)" />
                        <MetricRow label="30d Penalty Accrual" :value="formatCurrency(ud?.containers?.recent_penalties)" />
                        <div class="border-t border-slate-100 pt-3 mt-3">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Size Distribution</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="(count, size) in ud?.containers?.size_distribution" :key="size"
                                      class="text-[9px] font-black px-2 py-1 rounded-md bg-slate-100 text-slate-600">
                                    {{ size }}: {{ count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== NEW: Expense Analytics ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Expense Analytics</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Total (YTD)" :value="formatCurrency(ud?.expenses?.total)" color="red" />
                    <StatCard label="Pending" :value="formatCurrency(ud?.expenses?.pending)" color="amber" />
                    <StatCard label="Approved Items" :value="ud?.expenses?.approved_count" color="slate" />
                    <StatCard label="Pending Items" :value="ud?.expenses?.pending_count" color="amber" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- By Category -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">By Category</p>
                        <div class="space-y-1.5">
                            <div v-for="cat in ud?.expenses?.by_category" :key="cat.name"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600 truncate w-28">{{ cat.name }}</span>
                                <span class="font-black text-slate-500">{{ formatCurrency(cat.total) }}</span>
                            </div>
                            <div v-if="!ud?.expenses?.by_category?.length" class="text-[10px] text-slate-400 py-2">No expense data</div>
                        </div>
                    </div>
                    <!-- By Vehicle -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">By Vehicle (top)</p>
                        <div class="space-y-1.5">
                            <div v-for="v in ud?.expenses?.by_vehicle?.slice(0, 8)" :key="v.plate"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600">{{ v.plate }}</span>
                                <span class="font-black text-slate-500">{{ formatCurrency(v.total) }}</span>
                            </div>
                            <div v-if="!ud?.expenses?.by_vehicle?.length" class="text-[10px] text-slate-400 py-2">No vehicle expenses</div>
                        </div>
                    </div>
                    <!-- Monthly Trend -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Monthly Trend (YTD)</p>
                        <div class="space-y-1.5">
                            <div v-for="(val, idx) in ud?.expenses?.monthly_series" :key="idx"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600 w-12">{{ ud?.expenses?.monthly_labels?.[idx] }}</span>
                                <div class="flex-1 mx-2">
                                    <div class="bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full bg-rose-400 transition-all"
                                             :style="{ width: expenseBarWidth(val) }"></div>
                                    </div>
                                </div>
                                <span class="font-black text-slate-500 w-20 text-right">{{ formatCurrency(val) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== NEW: Performance Scores Summary ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Performance Scores</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                    <StatCard label="Drivers Scored" :value="ud?.performance?.drivers_scored" color="blue" />
                    <StatCard label="Avg Driver Score" :value="formatScore(ud?.performance?.avg_driver_score)" color="emerald" />
                    <StatCard label="Avg Dispatcher" :value="formatScore(ud?.performance?.avg_dispatcher_score)" color="purple" />
                    <StatCard label="Human Ratings" :value="ud?.performance?.total_human_ratings" color="amber" />
                    <StatCard label="On-Time Delivery" :value="formatScore(ud?.performance?.avg_on_time_delivery)" color="indigo" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <MetricRow label="Fuel Efficiency Avg" :value="formatScore(ud?.performance?.avg_fuel_efficiency)" />
                    <MetricRow label="Route Compliance Avg" :value="formatScore(ud?.performance?.avg_route_compliance)" />
                    <MetricRow label="Dispatchers Scored" :value="ud?.performance?.dispatchers_scored" />
                </div>
            </div>
        </section>

        <!-- ===== NEW: Wallet & Ledger Summary ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Wallet & Ledger Summary</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Total Wallets" :value="ud?.wallets?.total_wallets" color="slate" />
                    <StatCard label="Total Balance" :value="formatCurrency(ud?.wallets?.total_balance)" color="emerald" />
                    <StatCard label="Month Credits" :value="formatCurrency(ud?.wallets?.month_credits)" color="green" />
                    <StatCard label="Month Debits" :value="formatCurrency(ud?.wallets?.month_debits)" color="red" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Balance by Currency</p>
                        <div class="space-y-1.5">
                            <div v-for="wc in ud?.wallets?.by_currency" :key="wc.currency"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600">{{ wc.currency }} ({{ wc.count }} wallets)</span>
                                <span class="font-black text-slate-500">{{ formatCurrency(wc.total) }}</span>
                            </div>
                            <div v-if="!ud?.wallets?.by_currency?.length" class="text-[10px] text-slate-400 py-2">No wallets yet</div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <MetricRow label="Month Transactions" :value="ud?.wallets?.month_transactions" />
                        <MetricRow label="Net Flow" :value="formatCurrency(walletNetFlow)" :highlight="walletNetFlow >= 0" :warning="walletNetFlow < 0" />
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== NEW: Invoices / AR Summary ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Accounts Receivable</h2>
                <span v-if="ud?.invoices?.collection_rate !== undefined"
                      class="text-[9px] font-black px-2 py-0.5 rounded-md"
                      :class="ud.invoices.collection_rate >= 70 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ ud.invoices.collection_rate }}% Collection
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Total Invoices" :value="ud?.invoices?.total_invoices" color="slate" />
                    <StatCard label="Outstanding" :value="formatCurrency(ud?.invoices?.total_outstanding)" color="red" />
                    <StatCard label="Collected" :value="formatCurrency(ud?.invoices?.total_paid)" color="emerald" />
                    <StatCard label="Overdue" :value="formatCurrency(ud?.invoices?.overdue)" color="amber" />
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Status Distribution -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Status Distribution</p>
                        <div class="space-y-1.5">
                            <div v-for="inv in ud?.invoices?.status_distribution" :key="inv.status"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600 capitalize w-24">{{ inv.status }}</span>
                                <span class="text-slate-400 w-12">{{ inv.count }} items</span>
                                <span class="font-black text-slate-500 w-24 text-right">{{ formatCurrency(inv.amount) }}</span>
                            </div>
                            <div v-if="!ud?.invoices?.status_distribution?.length" class="text-[10px] text-slate-400 py-2">No invoices</div>
                        </div>
                    </div>
                    <!-- Monthly Invoiced -->
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Monthly Invoiced (12mo)</p>
                        <div class="space-y-1.5">
                            <div v-for="(val, idx) in ud?.invoices?.monthly_series" :key="idx"
                                 class="flex items-center justify-between text-[10px]">
                                <span class="font-bold text-slate-600 w-12">{{ ud?.invoices?.monthly_labels?.[idx] }}</span>
                                <div class="flex-1 mx-2">
                                    <div class="bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-400 transition-all"
                                             :style="{ width: invBarWidth(val) }"></div>
                                    </div>
                                </div>
                                <span class="font-black text-slate-500 w-20 text-right">{{ formatCurrency(val) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== NEW: Trip Profitability ===== -->
        <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Trip Profitability (This Month)</h2>
                <span v-if="ud?.trip_profitability?.profit_margin !== undefined"
                      class="text-[9px] font-black px-2 py-0.5 rounded-md"
                      :class="ud.trip_profitability.profit_margin >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                    {{ ud.trip_profitability.profit_margin >= 0 ? '+' : '' }}{{ ud.trip_profitability.profit_margin }}% Margin
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <StatCard label="Delivered Trips" :value="ud?.trip_profitability?.total_trips" color="slate" />
                    <StatCard label="Revenue" :value="formatCurrency(ud?.trip_profitability?.total_revenue)" color="emerald" />
                    <StatCard label="Expenses" :value="formatCurrency(ud?.trip_profitability?.total_expenses)" color="red" />
                    <StatCard label="Net Profit" :value="formatCurrency(ud?.trip_profitability?.total_profit)" :color="tripProfitColor" />
                </div>
                <div v-if="ud?.trip_profitability?.top_trips?.length" class="border-t border-slate-100 pt-4">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Top 10 Profitable Trips</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-[10px]">
                            <thead>
                                <tr class="text-left text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                                    <th class="pb-2 pr-3">Reference</th>
                                    <th class="pb-2 pr-3">Vehicle</th>
                                    <th class="pb-2 pr-3">Driver</th>
                                    <th class="pb-2 pr-3 text-right">Revenue</th>
                                    <th class="pb-2 pr-3 text-right">Expenses</th>
                                    <th class="pb-2 pr-3 text-right">Profit</th>
                                    <th class="pb-2 text-right">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in ud.trip_profitability.top_trips" :key="t.id"
                                    class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="py-2 pr-3 font-bold text-slate-700">{{ t.reference }}</td>
                                    <td class="py-2 pr-3 text-slate-500">{{ t.vehicle || '—' }}</td>
                                    <td class="py-2 pr-3 text-slate-500">{{ t.driver || '—' }}</td>
                                    <td class="py-2 pr-3 text-right font-bold text-emerald-600">{{ formatCurrency(t.revenue) }}</td>
                                    <td class="py-2 pr-3 text-right font-bold text-red-500">{{ formatCurrency(t.expenses) }}</td>
                                    <td class="py-2 pr-3 text-right font-black" :class="t.profit >= 0 ? 'text-emerald-700' : 'text-red-600'">{{ formatCurrency(t.profit) }}</td>
                                    <td class="py-2 text-right font-black" :class="t.margin >= 0 ? 'text-emerald-600' : 'text-red-500'">{{ t.margin >= 0 ? '+' : '' }}{{ t.margin }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="text-[10px] text-slate-400 text-center py-4">No delivered trips this month</div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { api } from '../../../plugins/axios'
import Chart from 'chart.js/auto'
import StatCard from '../../components/ui/StatCard.vue'
import MetricRow from '../../components/ui/MetricRow.vue'

const loading = ref(false)
const data = ref(null)
const ud = ref(null)
const clientChart = ref(null)
const tripChart = ref(null)
const orderChart = ref(null)
const orderTimelineChart = ref(null)
const fuelChart = ref(null)
const financialChart = ref(null)

const currencies = ref([])
const selectedCurrencyId = ref(null)

let charts = []

const selectedCurrency = computed(() => {
    return currencies.value.find(c => c.id === selectedCurrencyId.value) || { code: 'RWF', symbol: 'RWF' }
})

const shortenNumber = (val) => {
    if (val == null || isNaN(val)) return '—'
    const abs = Math.abs(val)
    if (abs >= 1_000_000_000) return (val / 1_000_000_000).toLocaleString('en-US', { maximumFractionDigits: 2 }) + 'B'
    if (abs >= 1_000_000) return (val / 1_000_000).toLocaleString('en-US', { maximumFractionDigits: 2 }) + 'M'
    if (abs >= 1_000) return (val / 1_000).toLocaleString('en-US', { maximumFractionDigits: 2 }) + 'k'
    return Number(val).toLocaleString('en-US', { maximumFractionDigits: 2 })
}

const formatCurrency = (val) => {
    if (val == null) return '—'
    const c = selectedCurrency.value
    const prefix = c.code === 'RWF' ? 'RWF ' : c.symbol + ' '
    return prefix + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatScore = (val) => {
    if (val == null) return '—'
    return Number(val).toFixed(1) + '%'
}

const healthColor = computed(() => {
    const h = data.value?.compliance?.health_percentage ?? 100
    if (h >= 80) return 'bg-emerald-100 text-emerald-700'
    if (h >= 50) return 'bg-amber-100 text-amber-700'
    return 'bg-red-100 text-red-700'
})

const profitColor = computed(() => {
    const m = data.value?.financial?.profit_margin ?? 0
    return m >= 0 ? 'emerald' : 'red'
})

const tripProfitColor = computed(() => {
    const m = ud.value?.trip_profitability?.profit_margin ?? 0
    return m >= 0 ? 'emerald' : 'red'
})

const walletNetFlow = computed(() => {
    const credits = ud.value?.wallets?.month_credits ?? 0
    const debits = ud.value?.wallets?.month_debits ?? 0
    return credits - debits
})

const costBreakdown = computed(() => data.value?.financial?.cost_breakdown ?? [])

const maxCost = computed(() => {
    const items = costBreakdown.value
    return items.length ? Math.max(...items.map(i => Number(i.amount))) : 1
})

const costBarWidth = (amount) => {
    return maxCost.value > 0 ? (amount / maxCost.value) * 100 + '%' : '0%'
}

const costColor = (name) => {
    const colors = {
        'Fuel': '#f59e0b',
        'Repairs': '#ef4444',
        'Fines': '#8b5cf6',
        'Salaries': '#3b82f6',
        'Insurance': '#10b981',
        'Maintenance': '#f97316',
    }
    return colors[name] || '#94a3b8'
}

const containerStatusDist = computed(() => ud.value?.containers?.status_distribution ?? {})

const maxContainerCount = computed(() => {
    const vals = Object.values(containerStatusDist.value)
    return vals.length ? Math.max(...vals) : 1
})

const containerBarWidth = (count) => (count / maxContainerCount.value) * 100 + '%'

const containerStatusColor = (status) => {
    const colors = {
        in_transit: 'bg-blue-400',
        at_customer: 'bg-amber-400',
        empty_returned: 'bg-slate-400',
        at_warehouse: 'bg-purple-400',
        at_depot: 'bg-emerald-400',
    }
    return colors[status] || 'bg-indigo-400'
}

const maxExpenseVal = computed(() => {
    const vals = ud.value?.expenses?.monthly_series ?? []
    return vals.length ? Math.max(...vals) : 1
})

const expenseBarWidth = (val) => maxExpenseVal.value > 0 ? (val / maxExpenseVal.value) * 100 + '%' : '0%'

const maxInvVal = computed(() => {
    const vals = ud.value?.invoices?.monthly_series ?? []
    return vals.length ? Math.max(...vals) : 1
})

const invBarWidth = (val) => maxInvVal.value > 0 ? (val / maxInvVal.value) * 100 + '%' : '0%'

const destroyCharts = () => {
    charts.forEach(c => c.destroy())
    charts = []
}

const renderCharts = () => {
    destroyCharts()
    if (!data.value) return

    const d = data.value

    // Top Clients
    if (clientChart.value && d.business?.top_clients?.length) {
        const reversed = [...d.business.top_clients].reverse()
        const labels = reversed.map(c => c.name)
        const revenues = reversed.map(c => c.total_revenue)
        const barColors = [
            '#10b981', '#34d399', '#6ee7b7', '#059669', '#047857',
            '#3b82f6', '#60a5fa', '#93c5fd', '#2563eb', '#1d4ed8'
        ].slice(0, revenues.length)
        charts.push(new Chart(clientChart.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue', data: revenues,
                    backgroundColor: barColors,
                    borderRadius: 3,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { font: { size: 9 }, callback: (v) => selectedCurrency.value.symbol + shortenNumber(v) } },
                    y: { ticks: { font: { size: 8 } } }
                }
            }
        }))
    }

    // Trip Timeline
    if (tripChart.value && d.charts?.trip_timeline?.length) {
        const labels = d.charts.trip_timeline.map(t => t.date?.slice(5))
        const counts = d.charts.trip_timeline.map(t => t.count)
        charts.push(new Chart(tripChart.value, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Trips', data: counts, backgroundColor: '#3b82f6', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        }))
    }

    // Order Status
    if (orderChart.value && d.charts?.order_status_distribution?.length) {
        const statusColors = { draft: '#94a3b8', confirmed: '#3b82f6', in_transit: '#f59e0b', delivered: '#10b981', cancelled: '#ef4444' }
        const labels = d.charts.order_status_distribution.map(s => s.status)
        const counts = d.charts.order_status_distribution.map(s => s.count)
        const colors = d.charts.order_status_distribution.map(s => statusColors[s.status] || '#94a3b8')
        charts.push(new Chart(orderChart.value, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: counts, backgroundColor: colors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 9 }, boxWidth: 10 } } } }
        }))
    }

    // Orders This Week
    if (orderTimelineChart.value && d.charts?.order_timeline?.length) {
        const labels = d.charts.order_timeline.map(t => t.date?.slice(5))
        const counts = d.charts.order_timeline.map(t => t.count)
        charts.push(new Chart(orderTimelineChart.value, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Orders', data: counts, backgroundColor: '#3b82f6', borderRadius: 4 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        }))
    }

    // Fuel Consumption
    if (fuelChart.value && d.fuel?.monthly_consumption?.length) {
        const labels = d.financial?.monthly_labels || []
        const values = d.fuel.monthly_consumption
        charts.push(new Chart(fuelChart.value, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Liters', data: values, backgroundColor: '#f59e0b', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        }))
    }

    // Financial Chart — dual y-axis for revenue vs expenses
    if (financialChart.value && d.financial?.monthly_labels?.length) {
        charts.push(new Chart(financialChart.value, {
            type: 'bar',
            data: {
                labels: d.financial.monthly_labels,
                datasets: [
                    { label: 'Revenue', data: d.financial.monthly_revenue, backgroundColor: '#10b981', borderRadius: 3, yAxisID: 'y' },
                    { label: 'Expenses', data: d.financial.monthly_expenses, backgroundColor: '#ef4444', borderRadius: 3, yAxisID: 'y1' },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { font: { size: 9 }, boxWidth: 10 } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: { display: true, text: 'Revenue (' + selectedCurrency.value.symbol + ')', font: { size: 9 } },
                        ticks: { font: { size: 8 }, callback: (v) => selectedCurrency.value.symbol + shortenNumber(v) }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Costs (' + selectedCurrency.value.symbol + ')', font: { size: 9 } },
                        ticks: { font: { size: 8 }, callback: (v) => selectedCurrency.value.symbol + shortenNumber(v) }
                    }
                }
            }
        }))
    }
}

const refresh = async () => {
    loading.value = true
    try {
        const params = {}
        if (selectedCurrencyId.value) params.currency_id = selectedCurrencyId.value
        const { data: result } = await api.get('portal/reports', { params })
        data.value = result
        if (result.currencies?.length) {
            currencies.value = result.currencies
            if (!selectedCurrencyId.value) {
                const def = result.currencies.find(c => c.is_default) || result.currencies[0]
                selectedCurrencyId.value = def.id
            }
        }
        // Fetch unified data
        const { data: unified } = await api.get('portal/reports/unified', { params })
        ud.value = unified
        if (unified.currencies?.length && !selectedCurrencyId.value) {
            const def = unified.currencies.find(c => c.is_default) || unified.currencies[0]
            selectedCurrencyId.value = def.id
        }
        await nextTick()
        renderCharts()
    } catch (e) {
        console.error('Failed to load reports', e)
    } finally {
        loading.value = false
    }
}

const onCurrencyChange = () => {
    refresh()
}

let interval
onMounted(() => {
    refresh()
    interval = setInterval(refresh, 60000)
})

onUnmounted(() => {
    destroyCharts()
    clearInterval(interval)
})
</script>
<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
