<x-filament-panels::page>
    <div x-data="{ tab: 'list', filterText: '' }" class="space-y-4">

        <div class="flex border-b">
            <button class="px-4 py-2 text-sm font-medium"
                    :class="tab==='list'?'border-b-2 border-primary-600 text-primary-700':'text-gray-500'"
                    @click="tab='list'">Grouped by Region</button>
            <button class="px-4 py-2 text-sm font-medium"
                    :class="tab==='map'?'border-b-2 border-primary-600 text-primary-700':'text-gray-500'"
                    @click="tab='map'">Map View</button>
        </div>

        <div>
            <input type="text" placeholder="Filter by region/vehicle..." x-model="filterText"
                   class="fi-input"/>
        </div>

        <div x-show="tab==='list'" class="space-y-6">
            @php $grouped = $vehicles->groupBy('region'); @endphp
            @foreach($grouped as $region => $group)
                <x-filament::card>
                    <button class="w-full flex justify-between font-medium text-left"
                            @click="$el.nextElementSibling.classList.toggle('hidden')">
                        <span>{{ $region }} ({{ $group->count() }} vehicles)</span>
                        <span class="text-gray-500">▼</span>
                    </button>
                    <div class="hidden">
                        @foreach($group as $vehicle)
                            <div x-show="!filterText
                                || '{{ strtolower($region) }}'.includes(filterText.toLowerCase())
                                || '{{ strtolower($vehicle['name']) }}'.includes(filterText.toLowerCase())"
                                 class="p-4 fi-card-section-content grid grid-cols-1 md:grid-cols-3 gap-2 border-t">
                                <div>
                                    <p class="font-medium text-gray-800">Name: <strong>{{ $vehicle['name'] }}</strong></p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Last Seen: <strong>{{ \Carbon\Carbon::parse($vehicle['last_seen'])->diffForHumans() }}</strong></p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Speed: <strong>{{ $vehicle['speed'] }} km/h</strong></p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            @endforeach
        </div>

        <div x-show="tab==='map'" class="p-4 h-[600px]">
            <div id="fleet-map" class="w-full h-full rounded-xl border"></div>
        </div>
    </div>
</x-filament-panels::page>
