<template>
    <div class="flex flex-col h-screen bg-slate-50 overflow-hidden font-sans">
        <header class="p-6 pb-2 shrink-0 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Tracker</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Fleet Position &amp; Journey Monitoring</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">From</span>
                    <input type="date" v-model="dateFrom"
                           class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none" />
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">To</span>
                    <input type="date" v-model="dateTo"
                           class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none" />
                </div>
                <div class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Refresh</span>
                    <select v-model="refreshInterval"
                            class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none appearance-none cursor-pointer">
                        <option :value="0">Off</option>
                        <option :value="15">15s</option>
                        <option :value="30">30s</option>
                        <option :value="60">60s</option>
                    </select>
                </div>
            </div>
        </header>

        <div class="px-6 pb-3 shrink-0">
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input ref="searchInput" v-model="searchQuery" type="text" placeholder="Search by vehicle plate, driver name, or trailer plate..."
                       class="w-full pl-11 pr-4 py-3.5 bg-white rounded-2xl text-sm font-bold text-slate-700 border border-slate-200 shadow-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder:text-slate-300" />
                <button v-if="searchQuery" @click="clearSearch"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="px-6 pb-3 shrink-0 flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Total</span>
                <span class="text-sm font-black text-slate-800">{{ fleetAlerts.totalVehicles }}</span>
            </div>
            <div class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-slate-200 px-4 py-2.5">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Moving</span>
                <span class="text-sm font-black text-green-700">{{ fleetAlerts.movingCount }}</span>
            </div>
            <button @click="showStationaryModal = true" class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-amber-200 px-4 py-2.5 hover:shadow-md transition-shadow active:scale-[0.97] cursor-pointer">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Stationary</span>
                <select v-model="notMovedHours" @click.stop
                        class="text-[9px] font-black text-amber-700 bg-transparent outline-none border-none appearance-none cursor-pointer uppercase ml-1">
                    <option :value="6">6h</option>
                    <option :value="12">12h</option>
                    <option :value="24">24h</option>
                    <option :value="36">36h</option>
                    <option :value="48">48h</option>
                </select>
                <span class="text-sm font-black text-amber-700 ml-1">{{ fleetAlerts.notMovedCount }}</span>
            </button>
            <button @click="showOfflineModal = true" class="flex items-center gap-2 bg-white rounded-2xl shadow-sm border border-red-200 px-4 py-2.5 hover:shadow-md transition-shadow active:scale-[0.97] cursor-pointer">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Offline</span>
                <span class="text-sm font-black text-red-600 ml-1">{{ fleetAlerts.offlineCount }}</span>
            </button>
        </div>

        <div class="flex flex-1 overflow-hidden p-6 pt-2 gap-6">
            <div class="flex-1 bg-white rounded-[3rem] shadow-sm border border-slate-200 overflow-hidden relative z-10">
                <div id="tracker-map" class="h-full w-full"></div>

                <div v-if="!mapReady" class="absolute inset-0 bg-slate-50/70 backdrop-blur-md z-[2000] flex items-center justify-center">
                    <div class="flex flex-col items-center gap-4 bg-white p-6 rounded-3xl shadow-2xl border border-slate-100">
                        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Initializing Map Engine...</p>
                    </div>
                </div>
            </div>

            <div class="w-[400px] flex flex-col z-20 shrink-0">
                <div class="flex flex-col h-full bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="!selectedVehicle" class="flex flex-col h-full">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
                            <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Search Results</h2>
                            <p v-if="searchQuery && searchResults.length > 0" class="text-[9px] font-bold text-slate-400 mt-1">{{ searchResults.length }} vehicle(s) found</p>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-3">
                            <div v-if="searching" class="flex justify-center p-8">
                                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                            <div v-else-if="searchQuery && searchResults.length === 0 && !searching" class="text-center p-8 text-xs text-slate-400 font-bold uppercase">
                                No vehicles found matching "{{ searchQuery }}"
                            </div>
                            <div v-else-if="!searchQuery" class="text-center p-8 text-xs text-slate-400 font-bold uppercase">
                                Enter a search term to find vehicles
                            </div>
                            <div v-for="v in searchResults" :key="v.id"
                                 class="group bg-white p-5 rounded-2xl border border-slate-200 hover:border-blue-400 hover:shadow-md cursor-pointer transition-all relative overflow-hidden"
                                 @click="selectVehicle(v)">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-sm font-black text-slate-800 group-hover:text-blue-600 transition-colors">{{ v.plate_number }}</h3>
                                    <span v-if="v.snapshot?.is_moving"
                                          class="text-[9px] font-black px-2.5 py-1 rounded-md bg-green-100 text-green-700 uppercase">Moving</span>
                                    <span v-else-if="v.snapshot?.ignition"
                                          class="text-[9px] font-black px-2.5 py-1 rounded-md bg-amber-100 text-amber-700 uppercase">Idle</span>
                                    <span v-else
                                          class="text-[9px] font-black px-2.5 py-1 rounded-md bg-slate-100 text-slate-500 uppercase">Off</span>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span v-if="v.driver_name" class="text-[10px] font-bold text-slate-600">
                                        <span class="text-slate-400">Driver:</span> {{ v.driver_name }}
                                    </span>
                                    <span v-if="v.trailer_plate" class="text-[10px] font-bold text-slate-500">|</span>
                                    <span v-if="v.trailer_plate" class="text-[10px] font-bold text-slate-600">
                                        <span class="text-slate-400">Trailer:</span> {{ v.trailer_plate }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span v-if="v.snapshot" class="text-[9px] font-bold text-slate-400">
                                        {{ v.snapshot.latitude?.toFixed(4) }}, {{ v.snapshot.longitude?.toFixed(4) }}
                                    </span>
                                    <span v-else class="text-[9px] font-bold text-red-400">No GPS signal</span>
                                    <span v-if="v.snapshot?.last_seen_at" class="text-[9px] font-bold text-slate-400">
                                        {{ timeAgo(v.snapshot.last_seen_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col h-full">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                            <div>
                                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Vehicle Details</h2>
                                <p class="text-[9px] font-bold text-slate-400 mt-1">{{ selectedVehicle.plate_number }} — {{ selectedVehicle.driver_name || 'No Driver' }}</p>
                            </div>
                            <button @click="goBackToAllVehicles"
                                    class="text-[10px] font-black text-slate-400 hover:text-slate-700 uppercase px-3 py-1.5 rounded-lg hover:bg-slate-100 transition-colors active:scale-95">
                                &larr; Back
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-4">
                            <div v-if="vehicleLoading" class="flex justify-center p-8">
                                <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                            <template v-else-if="vehicleData">
                                <div class="bg-slate-900 p-5 rounded-[2rem] shadow-xl border border-slate-800 text-white relative overflow-hidden">
                                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl"></div>
                                    <div class="relative z-10">
                                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Live Status</p>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Plate</span>
                                                <p class="text-sm font-black">{{ vehicleData.vehicle.plate_number }}</p>
                                            </div>
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Trailer</span>
                                                <p class="text-sm font-black">{{ vehicleData.vehicle.trailer_plate || '—' }}</p>
                                            </div>
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Speed</span>
                                                <p class="text-sm font-black">{{ vehicleData.snapshot?.speed ?? '—' }} <span class="text-[9px] font-bold text-slate-400">km/h</span></p>
                                            </div>
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Fuel</span>
                                                <p class="text-sm font-black">{{ vehicleData.snapshot?.fuel_level ?? '—' }} <span class="text-[9px] font-bold text-slate-400">L</span></p>
                                            </div>
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Status</span>
                                                <p class="text-sm font-black">
                                                    <span v-if="vehicleData.snapshot?.is_moving" class="text-green-400">Moving</span>
                                                    <span v-else-if="vehicleData.snapshot?.ignition" class="text-amber-400">Idle</span>
                                                    <span v-else class="text-slate-500">Off</span>
                                                </p>
                                            </div>
                                            <div>
                                                <span class="text-[8px] font-bold text-slate-500 uppercase">Last Seen</span>
                                                <p class="text-sm font-black">{{ vehicleData.snapshot?.last_seen_at ? timeAgo(vehicleData.snapshot.last_seen_at) : '—' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="vehicleData.nearest_place" class="bg-emerald-50 p-5 rounded-[2rem] border border-emerald-200 relative overflow-hidden">
                                    <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-400/10 rounded-full blur-2xl"></div>
                                    <div class="relative z-10">
                                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-2">Nearest Place</p>
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <p class="text-sm font-black text-emerald-900">{{ vehicleData.nearest_place.name }}</p>
                                                <p class="text-[10px] font-bold text-emerald-600 mt-0.5">{{ vehicleData.nearest_place.type }} &middot; {{ vehicleData.nearest_place.city || 'N/A' }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-2xl font-black text-emerald-700">{{ formatDistance(vehicleData.nearest_place.distance_meters) }}</p>
                                                <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-wider">away</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="vehicleData.visited_places?.length" class="bg-indigo-50 p-5 rounded-[2rem] border border-indigo-200">
                                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-3">Visited Places Today</p>
                                    <div class="space-y-2.5">
                                        <div v-for="vp in vehicleData.visited_places" :key="vp.recorded_at + '-' + vp.place_id"
                                             class="flex items-center gap-3 bg-white p-3 rounded-xl border border-indigo-100">
                                            <div class="w-2 h-2 rounded-full shrink-0"
                                                 :class="vp.type === 'warehouse' || vp.type === 'depot' ? 'bg-amber-500' : 'bg-indigo-500'"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-black text-slate-800 truncate">{{ vp.name }}</p>
                                                <p class="text-[9px] font-bold text-slate-400">{{ formatTime(vp.recorded_at) }} &middot; {{ vp.distance }}m</p>
                                            </div>
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase bg-indigo-100 text-indigo-600 whitespace-nowrap">{{ vp.type }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Today's Breadcrumb</p>
                                    <div v-if="vehicleData.breadcrumb?.length" class="space-y-1 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                                        <div v-for="(pt, i) in sampledBreadcrumb" :key="pt.id"
                                             class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-slate-50 cursor-pointer"
                                             @click="flyToBreadcrumbPoint(pt)">
                                            <div class="flex flex-col items-center shrink-0">
                                                <div class="w-2.5 h-2.5 rounded-full"
                                                     :class="pt.ignition ? (pt.speed > 0 ? 'bg-green-500' : 'bg-amber-500') : 'bg-slate-300'"></div>
                                                <div v-if="i < sampledBreadcrumb.length - 1" class="w-px h-6 bg-slate-200 mt-0.5"></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-bold text-slate-700">{{ formatTime(pt.recorded_at) }}</p>
                                                <p class="text-[9px] font-bold text-slate-400">
                                                    {{ pt.latitude.toFixed(4) }}, {{ pt.longitude.toFixed(4) }}
                                                    <span v-if="pt.speed > 0"> &middot; {{ pt.speed }} km/h</span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span v-if="pt.ignition && pt.speed === 0"
                                                      class="text-[8px] font-black px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">Stop</span>
                                                <span v-if="pt.heading" class="text-[9px] text-slate-400" :style="{ display: 'inline-block', transform: 'rotate(' + pt.heading + 'deg)' }">&uarr;</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center p-6 text-xs text-slate-400 font-bold uppercase">
                                        No telemetry data for selected date range
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="showStationaryModal"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
             @click.self="showStationaryModal = false">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg max-h-[80vh] flex flex-col mx-4">
                <div class="flex items-center justify-between p-5 border-b border-slate-100 shrink-0">
                    <div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-tight">Stationary Vehicles</h2>
                        <p class="text-[10px] font-bold text-slate-400">{{ stationaryVehicles.length }} vehicle{{ stationaryVehicles.length === 1 ? '' : 's' }} in last</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select v-model="notMovedHours"
                                class="text-[10px] font-black text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-1.5 outline-none cursor-pointer uppercase">
                            <option :value="6">6h</option>
                            <option :value="12">12h</option>
                            <option :value="24">24h</option>
                            <option :value="36">36h</option>
                            <option :value="48">48h</option>
                        </select>
                        <button @click="showStationaryModal = false"
                                class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="px-5 pt-3 pb-2 shrink-0 flex items-center gap-2">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="stationarySearch" type="text" placeholder="Search by plate or driver..."
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all placeholder:text-slate-300" />
                    </div>
                    <button @click="exportStationary"
                            class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-[10px] font-black transition-all shadow-sm active:scale-95 uppercase tracking-wider">
                        Export Excel
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 pt-3 space-y-2">
                    <div v-for="v in filteredStationaryVehicles" :key="v.id"
                         class="flex items-center justify-between p-3 rounded-2xl bg-amber-50/50 border border-amber-100 hover:bg-amber-50 transition-colors">
                        <div>
                            <p class="text-sm font-black text-slate-800">{{ v.plate_number }}</p>
                            <p v-if="v.driver_name" class="text-[10px] font-bold text-slate-500">{{ v.driver_name }}</p>
                            <p v-else class="text-[10px] font-bold text-slate-400">No driver</p>
                        </div>
                        <div class="text-right">
                            <p v-if="v.distance_km !== null && v.distance_km !== undefined" class="text-[10px] font-black text-amber-700">{{ v.distance_km }} km</p>
                            <p v-if="v.snapshot?.last_seen_at" class="text-[9px] font-bold text-slate-400">{{ timeAgo(v.snapshot.last_seen_at) }}</p>
                        </div>
                    </div>
                    <div v-if="filteredStationaryVehicles.length === 0" class="p-10 text-center">
                        <p class="text-xs font-bold text-slate-400">{{ stationarySearch ? 'No vehicles match your search' : 'No stationary vehicles' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="showOfflineModal"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
             @click.self="showOfflineModal = false">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg max-h-[80vh] flex flex-col mx-4">
                <div class="flex items-center justify-between p-5 border-b border-slate-100 shrink-0">
                    <div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-tight">Offline Vehicles</h2>
                        <p class="text-[10px] font-bold text-slate-400">{{ offlineVehicles.length }} vehicle{{ offlineVehicles.length === 1 ? '' : 's' }} in last</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select v-model="notMovedHours"
                                class="text-[10px] font-black text-red-700 bg-red-50 border border-red-200 rounded-xl px-3 py-1.5 outline-none cursor-pointer uppercase">
                            <option :value="6">6h</option>
                            <option :value="12">12h</option>
                            <option :value="24">24h</option>
                            <option :value="36">36h</option>
                            <option :value="48">48h</option>
                        </select>
                        <button @click="showOfflineModal = false"
                                class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="px-5 pt-3 pb-2 shrink-0 flex items-center gap-2">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="offlineSearch" type="text" placeholder="Search by plate or driver..."
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 transition-all placeholder:text-slate-300" />
                    </div>
                    <button @click="exportOffline"
                            class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-[10px] font-black transition-all shadow-sm active:scale-95 uppercase tracking-wider">
                        Export Excel
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 pt-3 space-y-2">
                    <div v-for="v in filteredOfflineVehicles" :key="v.id"
                         class="flex items-center justify-between p-3 rounded-2xl bg-red-50/50 border border-red-100 hover:bg-red-50 transition-colors">
                        <div>
                            <p class="text-sm font-black text-slate-800">{{ v.plate_number }}</p>
                            <p v-if="v.driver_name" class="text-[10px] font-bold text-slate-500">{{ v.driver_name }}</p>
                            <p v-else class="text-[10px] font-bold text-slate-400">No driver</p>
                        </div>
                        <div class="text-right">
                            <p v-if="v.snapshot?.last_seen_at" class="text-[10px] font-black text-red-600">{{ timeAgo(v.snapshot.last_seen_at) }}</p>
                            <p v-else class="text-[10px] font-black text-red-600">No signal</p>
                        </div>
                    </div>
                    <div v-if="filteredOfflineVehicles.length === 0" class="p-10 text-center">
                        <p class="text-xs font-bold text-slate-400">{{ offlineSearch ? 'No vehicles match your search' : 'No offline vehicles' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { trackerApi } from "../../api/tracker";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const selectedVehicle = ref(null);
const vehicleData = ref(null);
const vehicleLoading = ref(false);
const mapReady = ref(false);
const dateFrom = ref('');
const dateTo = ref('');
const refreshInterval = ref(0);
const allVehicles = ref([]);
const notMovedHours = ref(12);
const showStationaryModal = ref(false);
const showOfflineModal = ref(false);
let searchInput = ref(null);
let refreshTimer = null;

const fleetAlerts = reactive({ totalVehicles: 0, movingCount: 0, notMovedCount: 0, offlineCount: 0 });

const recalcAlerts = () => {
    const vehicles = allVehicles.value;
    const totalVehicles = vehicles.length;
    const now = Date.now();
    const thresholdMs = notMovedHours.value * 60 * 60 * 1000;

    let movingCount = 0;
    let notMovedCount = 0;
    let offlineCount = 0;

    for (const v of vehicles) {
        const snap = v.snapshot;
        if (!snap || !snap.last_seen_at) {
            offlineCount++;
            continue;
        }
        const lastSeen = new Date(snap.last_seen_at).getTime();
        const age = now - lastSeen;

        if (age > thresholdMs) {
            offlineCount++;
        } else if (v.distance_km !== null && v.distance_km !== undefined) {
            if (v.distance_km >= 2) {
                movingCount++;
            } else {
                notMovedCount++;
            }
        } else if (snap.is_moving) {
            movingCount++;
        } else {
            notMovedCount++;
        }
    }

    fleetAlerts.totalVehicles = totalVehicles;
    fleetAlerts.movingCount = movingCount;
    fleetAlerts.notMovedCount = notMovedCount;
    fleetAlerts.offlineCount = offlineCount;
};

watch([allVehicles, notMovedHours], recalcAlerts, { immediate: true });

const exportStationary = () => {
    window.location.href = `/api/portal/tracker/export-stationary?hours=${notMovedHours.value}`;
};

const exportOffline = () => {
    window.location.href = `/api/portal/tracker/export-offline?hours=${notMovedHours.value}`;
};

const stationarySearch = ref('');
const offlineSearch = ref('');

const stationaryVehicles = computed(() => {
    const now = Date.now();
    const thresholdMs = notMovedHours.value * 60 * 60 * 1000;
    return allVehicles.value.filter(v => {
        const snap = v.snapshot;
        if (!snap || !snap.last_seen_at) return false;
        const age = now - new Date(snap.last_seen_at).getTime();
        if (age > thresholdMs) return false;
        if (v.distance_km !== null && v.distance_km !== undefined) {
            return v.distance_km < 2;
        }
        return !snap.is_moving;
    });
});

const filteredStationaryVehicles = computed(() => {
    const q = stationarySearch.value.toLowerCase().trim();
    if (!q) return stationaryVehicles.value;
    return stationaryVehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q)
        || (v.driver_name && v.driver_name.toLowerCase().includes(q))
    );
});

const offlineVehicles = computed(() => {
    const now = Date.now();
    const thresholdMs = notMovedHours.value * 60 * 60 * 1000;
    return allVehicles.value.filter(v => {
        const snap = v.snapshot;
        if (!snap || !snap.last_seen_at) return true;
        const age = now - new Date(snap.last_seen_at).getTime();
        return age > thresholdMs;
    });
});

const filteredOfflineVehicles = computed(() => {
    const q = offlineSearch.value.toLowerCase().trim();
    if (!q) return offlineVehicles.value;
    return offlineVehicles.value.filter(v =>
        v.plate_number.toLowerCase().includes(q)
        || (v.driver_name && v.driver_name.toLowerCase().includes(q))
    );
});

watch(notMovedHours, () => {
    if (allVehicles.value.length > 0) {
        fetchAllVehicles();
    }
});

let map = null;
let vehicleMarker = null;
let nearestPlaceMarker = null;
let breadcrumbPolyline = null;
let placeMarkers = [];
let visitedPlaceMarkers = [];
let allVehiclesMarkers = [];

const typeBadgeClass = (type) => {
    const classes = {
        warehouse: 'bg-purple-100 text-purple-700',
        factory: 'bg-blue-100 text-blue-700',
        depot: 'bg-amber-100 text-amber-700',
        yard: 'bg-green-100 text-green-700',
        checkpoint: 'bg-red-100 text-red-700',
        terminal: 'bg-indigo-100 text-indigo-700',
        office: 'bg-slate-100 text-slate-700',
        fuel_station: 'bg-orange-100 text-orange-700',
        rest_stop: 'bg-teal-100 text-teal-700',
        border: 'bg-rose-100 text-rose-700',
    };
    return classes[type] || 'bg-slate-100 text-slate-700';
};

onMounted(() => {
    const today = new Date().toISOString().split('T')[0];
    dateFrom.value = today;
    dateTo.value = today;
    setTimeout(initMap, 500);
    setTimeout(fetchAllVehicles, 1200);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});

watch(refreshInterval, (val) => {
    if (refreshTimer) clearInterval(refreshTimer);
    if (val > 0) {
        refreshTimer = setInterval(() => {
            if (selectedVehicle.value) {
                fetchVehicleData(selectedVehicle.value.id);
            } else if (!searchQuery.value || searchQuery.value.length < 2) {
                fetchAllVehicles();
            }
        }, val * 1000);
    }
});

watch([dateFrom, dateTo], () => {
    if (selectedVehicle.value) {
        fetchVehicleData(selectedVehicle.value.id);
    }
});

let searchTimeout = null;
let prevSearchHadValue = false;
watch(searchQuery, (val) => {
    const hasVal = val && val.length >= 2;
    if (!hasVal && prevSearchHadValue) {
        clearAllVehiclesMarkers();
        searchResults.value = [];
        renderAllVehiclesMarkers();
    } else if (hasVal && !prevSearchHadValue) {
        clearAllVehiclesMarkers();
    }
    prevSearchHadValue = hasVal;
    if (!hasVal) return;
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => doSearch(val), 300);
});

const clearSearch = () => {
    searchQuery.value = '';
    searchResults.value = [];
};

const doSearch = async (q) => {
    searching.value = true;
    try {
        const res = await trackerApi.searchVehicles(q);
        searchResults.value = res.data;
    } catch (e) {
        console.error('Search failed', e);
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
};

const goBackToAllVehicles = () => {
    selectedVehicle.value = null;
    clearMapLayers();
    renderAllVehiclesMarkers();
};

const selectVehicle = async (v) => {
    clearAllVehiclesMarkers();
    selectedVehicle.value = v;
    await fetchVehicleData(v.id);
};

const fetchVehicleData = async (vehicleId) => {
    vehicleLoading.value = true;
    try {
        const params = {};
        if (dateFrom.value) params.date_from = dateFrom.value + ' 00:00:00';
        if (dateTo.value) params.date_to = dateTo.value + ' 23:59:59';
        const res = await trackerApi.getVehicle(vehicleId, params);
        vehicleData.value = res.data;
        nextTick(() => renderMap(vehicleData.value));
    } catch (e) {
        console.error('Failed to fetch vehicle data', e);
        vehicleData.value = null;
    } finally {
        vehicleLoading.value = false;
    }
};

const sampledBreadcrumb = computed(() => {
    if (!vehicleData.value?.breadcrumb?.length) return [];
    const pts = vehicleData.value.breadcrumb;
    if (pts.length <= 100) return pts;
    const step = Math.floor(pts.length / 100);
    const result = [];
    for (let i = 0; i < pts.length; i += step) {
        result.push(pts[i]);
    }
    if (result[result.length - 1]?.id !== pts[pts.length - 1]?.id) {
        result.push(pts[pts.length - 1]);
    }
    return result;
});

const timeAgo = (dateStr) => {
    const now = new Date();
    const d = new Date(dateStr);
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return d.toLocaleDateString();
};

const formatTime = (dateStr) => {
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDistance = (meters) => {
    if (!meters && meters !== 0) return '—';
    if (meters < 1000) return Math.round(meters) + 'm';
    return (meters / 1000).toFixed(1) + 'km';
};

const fetchAllVehicles = async () => {
    try {
        const res = await trackerApi.getAllVehicles({ hours: notMovedHours.value });
        allVehicles.value = res.data;
        if (!selectedVehicle.value && (!searchQuery.value || searchQuery.value.length < 2)) {
            renderAllVehiclesMarkers();
        }
    } catch (e) {
        console.error('Failed to fetch all vehicles', e);
    }
};

const clearAllVehiclesMarkers = () => {
    allVehiclesMarkers.forEach(m => { if (map) map.removeLayer(m); });
    allVehiclesMarkers = [];
};

const renderAllVehiclesMarkers = () => {
    clearAllVehiclesMarkers();
    if (!map || !allVehicles.value.length) return;
    const latlngs = [];
    allVehicles.value.forEach(v => {
        if (!v.snapshot?.latitude || !v.snapshot?.longitude) return;
        const latlng = [v.snapshot.latitude, v.snapshot.longitude];
        latlngs.push(latlng);

        const color = v.snapshot.is_moving ? '#10b981'
            : v.snapshot.ignition ? '#f59e0b'
            : '#94a3b8';

        const marker = L.circleMarker(latlng, {
            radius: 7,
            color: color,
            fillColor: '#fff',
            fillOpacity: 0.8,
            weight: 2.5,
        }).addTo(map);

        const tooltipHtml = `<div class="font-sans"><p class="font-black text-xs">${v.plate_number}</p>${v.driver_name ? `<p class="text-[10px] text-slate-500">${v.driver_name}</p>` : ''}${v.snapshot.speed > 0 ? `<p class="text-[10px] text-slate-400">${v.snapshot.speed} km/h</p>` : ''}</div>`;
        marker.bindTooltip(tooltipHtml, { direction: 'top', className: 'text-[9px] font-bold' });

        allVehiclesMarkers.push(marker);
    });

    if (latlngs.length > 1) {
        try {
            map.fitBounds(L.latLngBounds(latlngs).pad(0.15), { padding: [40, 40], maxZoom: 10 });
        } catch (e) { /* ignore bounds error */ }
    } else if (latlngs.length === 1) {
        map.setView(latlngs[0], 12);
    }
};

const initMap = () => {
    map = L.map('tracker-map', { zoomControl: false }).setView([-1.9441, 30.0619], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    setTimeout(() => {
        map.invalidateSize();
        mapReady.value = true;
    }, 400);
};

const clearMapLayers = () => {
    if (vehicleMarker) { map.removeLayer(vehicleMarker); vehicleMarker = null; }
    if (nearestPlaceMarker) { map.removeLayer(nearestPlaceMarker); nearestPlaceMarker = null; }
    if (breadcrumbPolyline) { map.removeLayer(breadcrumbPolyline); breadcrumbPolyline = null; }
    placeMarkers.forEach(m => map.removeLayer(m));
    placeMarkers = [];
    visitedPlaceMarkers.forEach(m => map.removeLayer(m));
    visitedPlaceMarkers = [];
    clearAllVehiclesMarkers();
};

const renderMap = (data) => {
    if (!map) return;
    clearMapLayers();

    if (data.places) {
        data.places.forEach(p => {
            if (!p.latitude || !p.longitude) return;
            const marker = L.circleMarker([p.latitude, p.longitude], {
                radius: 6,
                color: '#94a3b8',
                fillColor: '#cbd5e1',
                fillOpacity: 0.6,
                weight: 1.5,
            }).addTo(map);
            marker.bindTooltip(p.name, { direction: 'top', className: 'text-[9px] font-bold' });
            placeMarkers.push(marker);
        });
    }

    if (data.visited_places) {
        data.visited_places.forEach(vp => {
            if (!vp.latitude && !vp.longitude) return;
            const foundPlace = data.places?.find(p => p.id === vp.place_id);
            if (!foundPlace?.latitude) return;
            const marker = L.circleMarker([foundPlace.latitude, foundPlace.longitude], {
                radius: 10,
                color: '#6366f1',
                fillColor: '#818cf8',
                fillOpacity: 0.5,
                weight: 2,
                dashArray: '4,4',
            }).addTo(map);
            marker.bindTooltip(vp.name + ' (' + vp.distance + 'm @ ' + formatTime(vp.recorded_at) + ')');
            visitedPlaceMarkers.push(marker);
        });
    }

    if (data.breadcrumb?.length) {
        const latlngs = data.breadcrumb.map(p => [p.latitude, p.longitude]).filter(([lat, lng]) => lat && lng);
        if (latlngs.length > 1) {
            breadcrumbPolyline = L.polyline(latlngs, {
                color: '#3b82f6',
                weight: 3,
                opacity: 0.6,
                dashArray: '8,12',
            }).addTo(map);
        }

        if (latlngs.length > 1) {
            const start = latlngs[0];
            const end = latlngs[latlngs.length - 1];
            L.circleMarker(start, { radius: 5, color: '#10b981', fillColor: '#34d399', fillOpacity: 0.8, weight: 2 }).addTo(map)
                .bindTooltip('Start ' + formatTime(data.breadcrumb[0].recorded_at));
            L.circleMarker(end, { radius: 6, color: '#ef4444', fillColor: '#f87171', fillOpacity: 0.8, weight: 2 }).addTo(map)
                .bindTooltip('End ' + formatTime(data.breadcrumb[data.breadcrumb.length - 1].recorded_at));
        }
    }

    if (data.snapshot?.latitude && data.snapshot?.longitude) {
        const latlng = [data.snapshot.latitude, data.snapshot.longitude];
        const vehicleIcon = L.divIcon({
            className: '',
            html: `<div class="w-8 h-8 bg-blue-600 border-3 border-white rounded-full shadow-lg flex items-center justify-center" style="box-shadow: 0 0 0 3px rgba(59,130,246,0.3)"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l2-6h10l2 6M5 10v6h14v-6M5 10h14M7 16h2v2H7v-2zm8 0h2v2h-2v-2z"/></svg></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
        vehicleMarker = L.marker(latlng, { icon: vehicleIcon, zIndexOffset: 1000 }).addTo(map);
        const popupHtml = `<div class="font-sans"><p class="font-black text-sm">${data.vehicle.plate_number}</p><p class="text-xs text-slate-500">${data.snapshot.speed || 0} km/h</p></div>`;
        vehicleMarker.bindPopup(popupHtml);
    }

    if (data.nearest_place?.latitude && data.nearest_place?.longitude) {
        const nearestIcon = L.divIcon({
            className: '',
            html: `<div class="w-6 h-6 bg-emerald-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center"><svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });
        nearestPlaceMarker = L.marker([data.nearest_place.latitude, data.nearest_place.longitude], { icon: nearestIcon }).addTo(map);
        nearestPlaceMarker.bindTooltip(data.nearest_place.name + ' (' + formatDistance(data.nearest_place.distance_meters) + ')');
    }

    const allLatLngs = [];
    if (data.snapshot?.latitude) allLatLngs.push([data.snapshot.latitude, data.snapshot.longitude]);
    if (data.nearest_place?.latitude) allLatLngs.push([data.nearest_place.latitude, data.nearest_place.longitude]);
    if (data.breadcrumb?.length) {
        data.breadcrumb.forEach(p => {
            if (p.latitude) allLatLngs.push([p.latitude, p.longitude]);
        });
    }

    if (allLatLngs.length > 1) {
        try {
            map.fitBounds(L.latLngBounds(allLatLngs).pad(0.15), { padding: [40, 40], maxZoom: 15 });
        } catch (e) {
            if (data.snapshot?.latitude) map.setView([data.snapshot.latitude, data.snapshot.longitude], 12);
        }
    } else if (allLatLngs.length === 1) {
        map.setView(allLatLngs[0], 12);
    }
};

const flyToBreadcrumbPoint = (pt) => {
    if (!map || !pt.latitude || !pt.longitude) return;
    map.setView([pt.latitude, pt.longitude], 15);
};
</script>

<style>
#tracker-map { z-index: 1 !important; }
#tracker-map .leaflet-container { font-family: inherit; border-radius: 3rem !important; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
