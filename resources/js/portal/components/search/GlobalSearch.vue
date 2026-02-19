<template>
    <div class="relative w-full group" v-click-outside="closeSearch">
        <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
                v-model="query"
                type="text"
                placeholder="Search drivers, plates, orders, trips..."
                class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all text-sm"
                @keydown.esc="closeSearch"
            />

            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center">
                <Loader2 v-if="loading" class="w-4 h-4 text-orange-600 animate-spin" />
                <button v-else-if="query" @click="query = ''" class="text-gray-400 hover:text-gray-600">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <Transition name="slide-fade">
            <div v-if="results.length > 0 && query.length > 1"
                 class="absolute top-full left-0 right-0 bg-white border border-gray-100 rounded-2xl shadow-2xl mt-2 py-2 z-[100] max-h-[450px] overflow-y-auto">

                <div v-for="group in results" :key="group.type">
                    <template v-if="group.items.length">
                        <div class="px-4 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50/50 flex items-center gap-2">
                            <component :is="getIcon(group.type)" class="w-3 h-3" />
                            {{ group.type }}
                        </div>

                        <ul>
                            <li v-for="item in group.items" :key="item.id + item.type"
                                @click="handleSelection(item)"
                                class="px-4 py-3 hover:bg-orange-50 cursor-pointer flex items-center justify-between group/item transition-colors">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-700 group-hover/item:text-orange-700">
                                        {{ item.label }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ item.sublabel }}
                                    </span>
                                </div>
                                <ChevronRight class="w-4 h-4 text-gray-300 group-hover/item:text-orange-400 group-hover/item:translate-x-1 transition-all" />
                            </li>
                        </ul>
                    </template>
                </div>

                <div v-if="isResultEmpty" class="px-4 py-8 text-center">
                    <p class="text-sm text-gray-500">No results found for "{{ query }}"</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { api } from '../../../plugins/axios';
import debounce from 'lodash/debounce';
import {
    Search, Loader2, ChevronRight, X,
    User, Truck, MapPin, Package, ClipboardList
} from 'lucide-vue-next';

// 1. Local Directive Registration (Fixes the [Vue warn])
import clickOutside from '../../directives/clickOutside';
const vClickOutside = clickOutside;

const query = ref('');
const results = ref([]);
const loading = ref(false);

// 2. Icon Mapping for categories
const getIcon = (type) => {
    const icons = {
        'Drivers': User,
        'Vehicles': Truck,
        'Trailers': Truck, // Or a specific trailer icon
        'Orders': Package,
        'Trips': MapPin,
        'Clients': ClipboardList
    };
    return icons[type] || Search;
};

// 3. Search Logic
const performSearch = debounce(async () => {
    if (query.value.length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;
    try {
        // This hits your refactored SearchController@search method
        const { data } = await api.get('/search/global', {
            params: { q: query.value }
        });
        results.value = data;
    } catch (error) {
        console.error("Global search error:", error);
    } finally {
        loading.value = false;
    }
}, 300);

// Watcher for live autocomplete
watch(query, () => {
    performSearch();
});

const closeSearch = () => {
    results.value = [];
};

const isResultEmpty = computed(() => {
    return results.value.every(group => group.items.length === 0);
});

const handleSelection = (item) => {
    console.log("Selected:", item);
    // Add your routing logic here, e.g.:
    // router.push({ name: `${item.type.toLowerCase()}.show`, params: { id: item.id } });
    query.value = '';
    results.value = [];
};
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.2s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.15s ease-in;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

/* Custom Scrollbar for the results dropdown */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
