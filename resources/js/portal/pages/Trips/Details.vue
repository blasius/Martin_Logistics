<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div v-if="loading" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-slate-400 font-bold text-xs uppercase">Loading...</p>
        </div>

        <div v-else-if="!trip" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm text-xs text-slate-400 font-bold uppercase">
            Trip not found.
        </div>

        <template v-else>
            <div class="flex items-center justify-between">
                <div>
                    <RouterLink to="/trips/all" class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-wider text-indigo-600 hover:text-indigo-800 mb-2">
                        <ArrowLeft class="w-4 h-4" /> All Trips
                    </RouterLink>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ trip.reference }}</h1>
                    <p class="text-xs font-bold text-slate-400">Order {{ trip.order?.reference || '—' }} · Created {{ formatDateTime(trip.created_at) }}</p>
                </div>
                <span :class="statusBadgeClass(trip.status)"
                    class="inline-flex items-center gap-1 text-[10px] font-black px-4 py-2 rounded-full uppercase">
                    {{ trip.status }}
                </span>
            </div>

            <!-- Status Pipeline -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-5">Trip Lifecycle</h2>
                <div class="flex items-center overflow-x-auto pb-2">
                    <template v-for="(step, i) in pipeline" :key="step.status">
                        <div class="flex items-center shrink-0">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-black transition-all"
                                    :class="pipelineClass(step.status)">
                                    <Check v-if="pipelineState(step.status) === 'done'" class="w-4 h-4" />
                                    <span v-else-if="pipelineState(step.status) === 'current'">{{ i + 1 }}</span>
                                    <span v-else class="text-slate-400">{{ i + 1 }}</span>
                                </div>
                                <span class="mt-2 text-[9px] font-black uppercase tracking-wider"
                                    :class="pipelineState(step.status) === 'done' || pipelineState(step.status) === 'current' ? 'text-slate-800' : 'text-slate-400'">
                                    {{ step.label }}
                                </span>
                            </div>
                            <div v-if="i < pipeline.length - 1" class="w-14 h-0.5 mx-2 mb-6"
                                :class="pipelineState(step.status) === 'done' ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden lg:col-span-2">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Order Details</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-baseline justify-between mb-1">
                            <span class="font-black text-lg text-slate-800">{{ trip.order?.reference || '—' }}</span>
                            <span v-if="trip.order?.client_name" class="text-sm font-bold text-slate-500">{{ trip.order.client_name }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm font-bold text-slate-700 mb-4">
                            <span>{{ trip.order?.origin || '—' }}</span>
                            <ArrowRight class="w-4 h-4 text-indigo-500" />
                            <span>{{ trip.order?.destination || '—' }}</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <InfoCell label="Pickup Date" :value="trip.order?.pickup_date" />
                            <InfoCell label="Order Status" :value="trip.order?.status" />
                            <InfoCell label="Weight" :value="trip.order?.weight_kg ? `${trip.order.weight_kg} kg` : '—'" />
                            <InfoCell label="Price" :value="trip.order?.price ? `$${Number(trip.order.price).toLocaleString()}` : '—'" />
                        </div>
                        <p v-if="trip.order?.notes" class="mt-4 text-xs text-slate-500 italic">{{ trip.order.notes }}</p>
                    </div>
                </div>

                <!-- Execution -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Execution</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <Car class="w-5 h-5 text-slate-400" />
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Vehicle</p>
                                <p class="font-black text-slate-800">{{ trip.vehicle_plate_snapshot || '—' }}
                                    <span v-if="trip.trailer_plate_snapshot" class="text-amber-600"> + {{ trip.trailer_plate_snapshot }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <User class="w-5 h-5 text-slate-400" />
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Driver</p>
                                <p class="font-black text-slate-800">{{ trip.driver_name_snapshot || '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Route class="w-5 h-5 text-slate-400" />
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Route</p>
                                <p class="font-black text-slate-800">{{ trip.route?.name || '—' }}</p>
                                <p v-if="trip.route?.estimated_distance_km" class="text-xs font-bold text-slate-500">{{ trip.route.estimated_distance_km }} km planned</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Times & Deviation -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Timeline</h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <InfoCell label="Created" :value="formatDateTime(trip.created_at)" />
                        <InfoCell label="Departure" :value="formatDateTime(trip.departure_time)" />
                        <InfoCell label="Arrival" :value="formatDateTime(trip.arrival_time)" />
                        <InfoCell label="Planned Distance" :value="trip.planned_distance_km ? `${trip.planned_distance_km} km` : '—'" />
                        <InfoCell label="Actual Distance" :value="trip.actual_distance_km ? `${trip.actual_distance_km} km` : '—'" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Route Deviation</h2>
                    </div>
                    <div class="p-6">
                        <div v-if="trip.is_deviated" class="p-4 rounded-2xl bg-red-50 border border-red-200">
                            <p class="text-xs font-black text-red-700 uppercase tracking-wider mb-1">Deviated route detected</p>
                            <p v-if="trip.deviation_detected_at" class="text-sm font-bold text-red-600">{{ formatDateTime(trip.deviation_detected_at) }}</p>
                            <p v-if="trip.deviation_max_distance_meters" class="text-sm font-bold text-red-600 mt-1">Max deviation: {{ trip.deviation_max_distance_meters }} m</p>
                        </div>
                        <p v-else class="text-sm font-bold text-slate-500">No route deviation recorded.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                        <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Proof of Delivery</h2>
                    </div>
                    <div class="p-6">
                        <template v-if="trip.proof_of_delivery">
                            <p class="text-sm font-bold text-emerald-700 mb-1">Signed &amp; submitted</p>
                            <p class="text-xs font-bold text-slate-500">Received by: {{ trip.proof_of_delivery.received_by_name || '—' }}</p>
                            <p class="text-xs font-bold text-slate-500">Delivered: {{ formatDateTime(trip.proof_of_delivery.delivered_at) }}</p>
                        </template>
                        <p v-else class="text-sm font-bold text-slate-500">No proof of delivery recorded yet.</p>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div v-if="trip.histories && trip.histories.length" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                    <h2 class="text-xs font-black text-slate-500 uppercase tracking-wider">Activity History</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div v-for="h in trip.histories" :key="h.id" class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 mt-2 shrink-0"></div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-800">{{ h.action }}</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                {{ h.user_name || 'System' }} · {{ formatDateTime(h.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ArrowLeft, ArrowRight, Car, User, Route, Check } from 'lucide-vue-next';
import { tripsApi } from '../../api/trips';

const props = defineProps({ id: { type: [String, Number], required: true } });

const InfoCell = {
    props: { label: String, value: [String, Number, null] },
    template: `
        <div>
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400 mb-0.5">{{ label }}</p>
            <p class="font-bold text-sm text-slate-800">{{ value || '—' }}</p>
        </div>
    `,
};

const loading = ref(false);
const trip = ref(null);

const pipeline = [
    { status: 'pending', label: 'Pending' },
    { status: 'assigned', label: 'Assigned' },
    { status: 'on_route', label: 'On Route' },
    { status: 'delivered', label: 'Delivered' },
];

const statusIndex = computed(() => pipeline.findIndex(s => s.status === trip.value?.status));

const pipelineState = (status) => {
    if (!trip.value) return 'todo';
    const idx = pipeline.findIndex(s => s.status === status);
    const current = statusIndex.value;
    if (trip.value.status === 'cancelled') return 'todo';
    if (idx < current) return 'done';
    if (idx === current) return 'current';
    return 'todo';
};

const pipelineClass = (status) => {
    const state = pipelineState(status);
    if (state === 'done') return 'bg-emerald-500 text-white';
    if (state === 'current') return 'bg-indigo-600 text-white ring-4 ring-indigo-100';
    return 'bg-slate-100 text-slate-400';
};

const statusBadgeClass = (status) => {
    const map = {
        pending: 'bg-slate-100 text-slate-600',
        assigned: 'bg-blue-100 text-blue-700',
        on_route: 'bg-amber-100 text-amber-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const formatDateTime = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const fetchTrip = async () => {
    loading.value = true;
    try {
        const res = await tripsApi.show(props.id);
        trip.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch trip', e);
        trip.value = null;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchTrip);
</script>
