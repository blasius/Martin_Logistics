<template>
    <div class="flex justify-center select-none w-full">
        <svg viewBox="0 0 460 220" class="block w-full max-w-full">
            <defs>
                <!-- Glass Body Gradient -->
                <linearGradient :id="gid('glass')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.8" />
                    <stop offset="30%" stop-color="#f1f5f9" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#cbd5e1" stop-opacity="0.6" />
                </linearGradient>

                <!-- Amber Fuel Liquid Gradient -->
                <linearGradient :id="gid('liquid')" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="liquidTop" />
                    <stop offset="100%" :stop-color="liquidBottom" />
                </linearGradient>

                <!-- Cage Steel Ring Gradient -->
                <linearGradient :id="gid('steel')" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#94a3b8" />
                    <stop offset="50%" stop-color="#f8fafc" />
                    <stop offset="100%" stop-color="#64748b" />
                </linearGradient>

                <clipPath :id="gid('tankClip')">
                    <rect x="50" y="40" width="360" height="130" rx="65" ry="65" />
                </clipPath>
            </defs>

            <!-- Yellow Canopy Roof Line -->
            <rect x="20" y="10" width="420" height="14" rx="4" fill="#eab308" />
            <rect x="60" y="24" width="12" height="180" fill="#eab308" />
            <rect x="388" y="24" width="12" height="180" fill="#eab308" />
            <rect x="10" y="196" width="440" height="14" fill="#eab308" />

            <!-- Steel Support Saddles -->
            <rect x="120" y="160" width="24" height="38" fill="url(#gid('steel'))" rx="2" />
            <rect x="316" y="160" width="24" height="38" fill="url(#gid('steel'))" rx="2" />

            <!-- Tank Background / Empty Shell -->
            <rect x="50" y="40" width="360" height="130" rx="65" ry="65" fill="#1e293b" stroke="#64748b" stroke-width="4" />

            <!-- Fluid Level Clip -->
            <g :clip-path="`url(#${gid('tankClip')})`">
                <rect
                    x="40"
                    :y="liquidY"
                    width="380"
                    :height="liquidHeight"
                    :fill="`url(#${gid('liquid')})`"
                    class="transition-all duration-500 ease-in-out"
                />
                <!-- Surface Ripple Wave Line -->
                <line x1="40" :y1="liquidY" x2="420" :y2="liquidY" stroke="#ffffff" stroke-width="3" stroke-opacity="0.6" />
            </g>

            <!-- Transparent Glass Overlay -->
            <rect x="50" y="40" width="360" height="130" rx="65" ry="65" :fill="`url(#${gid('glass')})`" stroke="#94a3b8" stroke-width="3" />

            <!-- Outer Stainless Steel Rings -->
            <line x1="110" y1="40" x2="110" y2="170" :stroke="`url(#${gid('steel')})`" stroke-width="6" />
            <line x1="190" y1="40" x2="190" y2="170" :stroke="`url(#${gid('steel')})`" stroke-width="6" />
            <line x1="270" y1="40" x2="270" y2="170" :stroke="`url(#${gid('steel')})`" stroke-width="6" />
            <line x1="350" y1="40" x2="350" y2="170" :stroke="`url(#${gid('steel')})`" stroke-width="6" />

            <!-- Top Flanges / Manholes -->
            <rect x="160" y="28" width="30" height="12" fill="url(#gid('steel'))" rx="2" />
            <rect x="270" y="28" width="30" height="12" fill="url(#gid('steel'))" rx="2" />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    capacity: { type: Number, default: 39000 },
    currentLevel: { type: Number, default: 0 },
    reorderThreshold: { type: [Number, null], default: null },
    fuelType: { type: String, default: 'diesel' },
});

let uidCounter = 0;
const uid = `tg${Date.now().toString(36)}${uidCounter++}`;
const gid = (s) => `${uid}-${s}`;

const fillFraction = computed(() => {
    if (!props.capacity) return 0;
    return Math.max(0, Math.min(props.currentLevel / props.capacity, 1));
});

const isLow = computed(() => props.reorderThreshold != null && props.currentLevel <= props.reorderThreshold);

// Tank Height = 130px (Top Y: 40, Bottom Y: 170)
const liquidHeight = computed(() => fillFraction.value * 130);
const liquidY = computed(() => 170 - liquidHeight.value);

const liquidTop = computed(() => isLow.value ? '#f43f5e' : (props.fuelType === 'petrol' ? '#fbbf24' : '#f59e0b'));
const liquidBottom = computed(() => isLow.value ? '#be123c' : (props.fuelType === 'petrol' ? '#d97706' : '#b45309'));
</script>
